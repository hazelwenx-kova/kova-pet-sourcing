from pathlib import Path
import json,re,subprocess
ROOT=Path(__file__).resolve().parents[2];base=ROOT/'wordpress-migration'
allowed={'heading','text-editor','image','button','nested-accordion'}
manifest=json.loads((base/'assets/media-manifest.json').read_text())
assert len(manifest)==18
assert len({e['file'] for e in manifest})==14
assert all((base/'assets/images'/e['file']).is_file() for e in manifest)
for slug in ['home','cat-products']:
 data=json.loads((base/'templates'/f'kova-{slug}.json').read_text())
 assert data['version']=='0.4' and data['type']=='page'
 ids=set();counts={};copy=[]
 def check(nodes):
  for n in nodes:
   assert n['id'] not in ids;ids.add(n['id'])
   assert n['elType'] in ['container','widget']
   if n['elType']=='widget':
    kind=n['widgetType'];assert kind in allowed;counts[kind]=counts.get(kind,0)+1
    settings=n['settings']
    if kind=='image':
     assert settings['image']=={'id':0,'url':''}
     assert settings['_title'].startswith('图片：')
    if kind=='nested-accordion':
     assert settings['default_state']=='all_collapsed'
     assert settings['items'][0]['item_title']=='Explore sourcing options'
     assert len(n['elements'])==1
    if kind=='button':
     url=settings['link']['url'];assert '.html' not in url and (url.startswith('/') or url.startswith('#')),url
    copy.extend(settings.get(k,'') for k in ['title','editor','text'])
   check(n.get('elements',[]))
 check(data['content'])
 assert counts['image']==(8 if slug=='home' else 10)
 assert counts.get('nested-accordion',0)==(0 if slug=='home' else 8)
 text=' '.join(copy)
 assert not re.search(r'founder|industrial|production[- ]line projects',text,re.I)
 assert '.html' not in text
 assert 'AI-generated imagery is used for illustration only.' in text
 print(slug,counts,'PASS')
# The migration branch must not change existing static files/backend.
assert not subprocess.check_output(['git','diff','1a1b471','--','*.html','assets','api','server','INQUIRY_SETUP.md'],cwd=ROOT)
print('PASS: native templates, collapsed options, independent media, approved copy, static/backend preservation')
