"""Browser tests use mocked HTTPS responses, not a live submission service."""
from pathlib import Path
from playwright.sync_api import sync_playwright
URL='http://127.0.0.1:8001/contact.html'
ENDPOINT='https://inquiry.example.test/submit'
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path='/usr/bin/chromium',headless=True,args=['--no-sandbox'])
    page=browser.new_page(viewport={'width':1440,'height':1100})
    requests=[]
    page.on('request',lambda request: requests.append(request) if request.url==ENDPOINT else None)
    page.goto(URL,wait_until='networkidle')
    assert page.get_by_role('button',name='Send Sourcing Inquiry').is_disabled()
    assert page.locator('#submission-setup').is_visible()
    page.evaluate("document.querySelector('form').dispatchEvent(new Event('submit',{cancelable:true}))")
    assert 'No inquiry has been sent' in page.locator('#form-status').inner_text()
    assert not requests
    page.goto(URL,wait_until='networkidle');page.evaluate('document.fonts.ready')
    page.screenshot(path='/tmp/kova-contact-desktop-full.png',full_page=True)
    for width in [768,390]:
        page.set_viewport_size({'width':width,'height':844})
        assert page.evaluate('document.documentElement.scrollWidth <= innerWidth')
    page.screenshot(path='/tmp/kova-contact-mobile-full.png',full_page=True)
    def configured(route):
        response=route.fetch()
        route.fulfill(response=response,body=response.text().replace('data-endpoint=""',f'data-endpoint="{ENDPOINT}"'))
    page.route('**/contact.html*',configured)
    pending=[]
    page.route(ENDPOINT,lambda route:pending.append(route))
    page.goto(URL+'?category=Dog%20Travel%20Accessories')
    assert page.locator('#category').input_value()=='Dog Products'
    assert 'Dog Travel Accessories' in page.locator('#selected-category-note').inner_text()
    assert page.locator('#submission-setup').is_hidden()
    button=page.get_by_role('button',name='Send Sourcing Inquiry')
    button.click();assert not requests
    fields={'name':'Review Contact','email':'review@example.com','country':'United Kingdom','details':'Dog carrier materials and packaging options.'}
    for name,value in fields.items():page.locator(f'[name="{name}"]').fill(value)
    page.locator('[name="email"]').fill('invalid-email');button.click();assert not requests
    page.locator('[name="email"]').fill(fields['email'])
    page.locator('[name="website"]').evaluate("field => field.value='spam'")
    button.click();assert not requests
    page.locator('[name="website"]').evaluate("field => field.value=''")
    def send_and_fail(status=200,body='{"received":false}'):
        previous=len(requests)
        with page.expect_request(ENDPOINT):button.click()
        assert button.is_disabled()
        page.evaluate("document.querySelector('form').requestSubmit()")
        assert len(requests)==previous+1
        route=pending.pop(0)
        assert route.request.post_data_json['source_category']=='Dog Travel Accessories'
        route.fulfill(status=status,content_type='application/json',headers={'Access-Control-Allow-Origin':'*'},body=body)
        page.wait_for_function("!document.querySelector('#send-inquiry').disabled")
        expected = 'required fields' if status == 422 else 'Too many attempts' if status == 429 else 'could not confirm'
        assert expected in page.locator('#form-status').inner_text()
        assert page.locator('[name="details"]').input_value()==fields['details']
    send_and_fail()
    send_and_fail(status=500,body='{}')
    send_and_fail(body='not-json')
    send_and_fail(status=422,body='{}')
    send_and_fail(status=429,body='{}')
    with page.expect_request(ENDPOINT):button.click()
    pending.pop(0).abort('failed')
    page.wait_for_function("!document.querySelector('#send-inquiry').disabled")
    assert 'could not confirm' in page.locator('#form-status').inner_text()
    with page.expect_request(ENDPOINT):button.click()
    pending.pop(0).fulfill(status=200,content_type='application/json',headers={'Access-Control-Allow-Origin':'*'},body='{"received":true}')
    page.wait_for_function("document.querySelector('#form-status').textContent.includes('accepted for sending')")
    assert page.locator('[name="details"]').input_value()==''
    browser.close()
print('PASS: disabled preview/no transmission, category context, required/email validation, honeypot, duplicate prevention, HTTP/network/JSON/unconfirmed-receipt errors, mocked confirmed success; desktop/mobile screenshots')
