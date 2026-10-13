"""PHP/SMTP integration tests: synthetic credentials, trusted local TLS, no real mail sent.
KOVA_TEST_PHP may specify a PHP command with extensions; default: php.
"""
from pathlib import Path
import base64, json, os, secrets, shlex, shutil, socket, ssl, subprocess, tempfile, threading, time
from urllib.request import Request, urlopen
from urllib.error import HTTPError
ROOT=Path(__file__).resolve().parents[1]

def free_port():
    with socket.socket() as s:s.bind(('127.0.0.1',0));return s.getsockname()[1]
with tempfile.TemporaryDirectory(prefix='kova-inquiry-test-') as temp:
    private=Path(temp);shutil.copytree(ROOT/'server/phpmailer',private/'phpmailer')
    def openssl(*args):subprocess.run(['openssl',*args],cwd=private,check=True,stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
    openssl('req','-x509','-newkey','rsa:2048','-nodes','-keyout','ca.key','-out','ca.pem','-days','1','-subj','/CN=KOVA Local Test CA')
    openssl('req','-newkey','rsa:2048','-nodes','-keyout','server.key','-out','server.csr','-subj','/CN=localhost')
    (private/'extensions.txt').write_text('subjectAltName=DNS:localhost\nextendedKeyUsage=serverAuth\n')
    openssl('x509','-req','-in','server.csr','-CA','ca.pem','-CAkey','ca.key','-CAcreateserial','-out','server.pem','-days','1','-extfile','extensions.txt')
    context=ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER);context.load_cert_chain(private/'server.pem',private/'server.key')
    password=secrets.token_urlsafe(24);smtp_port=free_port();http_port=free_port()
    listener=socket.socket();listener.bind(('127.0.0.1',smtp_port));listener.listen();listener.settimeout(.2)
    state={'reject':False,'messages':[],'auth':[]};stop=threading.Event()
    def smtp_session(connection):
        try:
            stream=context.wrap_socket(connection,server_side=True).makefile('rwb')
            def write(line):stream.write((line+'\r\n').encode());stream.flush()
            def read():return stream.readline().decode().rstrip('\r\n')
            write('220 localhost test SMTP')
            while True:
                line=read()
                if not line:break
                command=line.split(' ',1)[0].upper()
                if command in ('EHLO','HELO'):
                    write('250-localhost');write('250-AUTH LOGIN');write('250 SIZE 50000')
                elif command=='AUTH':
                    write('334 VXNlcm5hbWU6');username=base64.b64decode(read()).decode()
                    write('334 UGFzc3dvcmQ6');provided=base64.b64decode(read()).decode()
                    assert username=='info@kovasourcing.com' and provided==password
                    state['auth'].append(True);write('235 Authentication successful')
                elif command in ('MAIL','RCPT'):write('250 OK')
                elif command=='DATA':
                    write('354 Send data');lines=[]
                    while True:
                        line=read()
                        if line=='.':break
                        lines.append(line)
                    if state['reject']:write('550 Message rejected for test')
                    else:state['messages'].append('\n'.join(lines));write('250 Accepted for sending')
                elif command=='QUIT':write('221 Bye');break
                elif command=='RSET':write('250 Reset')
                else:write('500 Unsupported command')
            stream.close()
        except (ssl.SSLError,ConnectionError,OSError):connection.close()
    def accept_loop():
        while not stop.is_set():
            try:connection,_=listener.accept()
            except socket.timeout:continue
            except OSError:break
            threading.Thread(target=smtp_session,args=(connection,),daemon=True).start()
    thread=threading.Thread(target=accept_loop,daemon=True);thread.start()
    config=private/'inquiry-config.php'
    def configure(enabled=True,host='localhost'):
        config.write_text("<?php return ['enabled'=>"+('true' if enabled else 'false')+",'allowed_origin'=>'https://kovasourcing.com','smtp'=>['host'=>'"+host+"','port'=>"+str(smtp_port)+",'security'=>'smtps','username'=>'info@kovasourcing.com','password'=>'"+password+"']];")
    configure(False)
    php=shlex.split(os.environ.get('KOVA_TEST_PHP','php'))
    env=os.environ.copy();env['KOVA_INQUIRY_CONFIG']=str(config);env['HTTPS']='on'
    router=private/'router.php'
    router.write_text("<?php $_SERVER['HTTPS']='on'; require "+repr(str(ROOT/'api/inquiry.php'))+";")
    log=open(private/'php.log','wb')
    process=subprocess.Popen(php+['-d','openssl.cafile='+str(private/'ca.pem'),'-S',f'127.0.0.1:{http_port}','-t',str(ROOT),str(router)],env=env,stdout=log,stderr=log)
    url=f'http://127.0.0.1:{http_port}/api/inquiry.php'
    def request(data=None,origin='https://kovasourcing.com',method='POST',content_type='application/json',raw=None):
        body=raw if raw is not None else json.dumps(data or {}).encode()
        req=Request(url,data=body if method=='POST' else None,method=method,headers={'Origin':origin,'Content-Type':content_type})
        try:
            with urlopen(req,timeout=15) as response:return response.status,json.load(response)
        except HTTPError as error:return error.code,json.load(error)
    try:
        for _ in range(50):
            try:request(method='GET');break
            except OSError:time.sleep(.1)
        valid={'name':'Review Contact','email':'review@example.com','company':'Example Brand','country':'United Kingdom','category':'Both','support':'Supplier Research','quantity':'Approximate range','details':'Fabric and packaging options.','website':''}
        assert request(valid)[0]==503
        router.write_text("<?php $_SERVER['HTTPS']='off'; require "+repr(str(ROOT/'api/inquiry.php'))+";")
        assert request(valid)[0]==403
        router.write_text("<?php $_SERVER['HTTPS']='on'; require "+repr(str(ROOT/'api/inquiry.php'))+";")
        configure()
        assert request(method='GET')[0]==405
        assert request(valid,origin='https://unrelated.example')[0]==403
        assert request(valid,content_type='text/plain')[0]==415
        assert request(raw=b'not-json')[0]==400
        assert request(raw=b' '*32769)[0]==413
        for changed in [{'email':'not-an-email'},{'name':'Name\r\nBcc: victim@example.com'},{'details':'x'*6001},{'name':''},{'category':'Invented'},{'support':'Unknown'},{'extra':'unexpected'}]:
            assert request(valid|changed)[0]==422,changed.keys()
        assert request(valid|{'website':'spam'})[0]==400
        assert not state['messages']
        status,response=request(valid);assert status==200 and response['received'] is True
        assert state['auth'] and len(state['messages'])==1
        message=state['messages'][0]
        assert 'From: KOVA Sourcing <info@kovasourcing.com>' in message
        assert 'To: info@kovasourcing.com' in message
        assert 'Reply-To: Review Contact <review@example.com>' in message
        state['reject']=True
        status,response=request(valid);assert status==503 and response['received'] is False
        state['reject']=False
        configure(host='127.0.0.1') # Valid CA, wrong hostname: verification must reject it.
        status,response=request(valid);assert status==503 and response['received'] is False
        configure()
        for _ in range(2):assert request(valid)[0]==200
        assert request(valid)[0]==429
        rate=json.loads((private/'state/limits.json').read_text());assert '127.0.0.1' not in json.dumps(rate)
        log.flush();assert password not in (private/'php.log').read_text()
        print('PASS: private disabled config, required/email/length/header validation, honeypot, origin/JSON rules, authenticated trusted TLS SMTP, fixed From/To, validated Reply-To, rejection errors, certificate hostname verification and rate limiting. No real email sent.')
    finally:
        process.terminate();process.wait(timeout=5);log.close();stop.set();listener.close();thread.join(timeout=1)
