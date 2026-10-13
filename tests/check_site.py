"""Run against the local preview: python3 tests/check_site.py."""
from pathlib import Path
from urllib.parse import urlsplit, unquote
from html.parser import HTMLParser
from playwright.sync_api import sync_playwright
ROOT = Path(__file__).resolve().parents[1]
PAGES = ['index.html','cat-products.html','dog-products.html','about.html','sourcing-services.html','how-it-works.html','contact.html']
class Links(HTMLParser):
    def handle_starttag(self, tag, attrs):
        for key, value in attrs:
            if key in ('href','src') and value and not urlsplit(value).scheme and not value.startswith('#'):
                assert (ROOT / unquote(urlsplit(value).path)).is_file(), value
for filename in PAGES:
    Links().feed((ROOT / filename).read_text())
print('PASS: local links and assets across all seven pages')
with sync_playwright() as p:
    browser = p.chromium.launch(executable_path='/usr/bin/chromium', headless=True, args=['--no-sandbox'])
    page = browser.new_page()
    errors = []
    failed_assets = []
    page.on('response', lambda response: failed_assets.append(response.url) if response.status >= 400 else None)
    page.on('pageerror', lambda error: errors.append(str(error)))
    for width in (1440,768,390):
        page.set_viewport_size({'width':width,'height':900})
        for filename in PAGES:
            response=page.goto(f'http://127.0.0.1:8001/{filename}')
            assert response.status==200
            assert page.locator('h1').count()==1
            assert page.evaluate('document.documentElement.scrollWidth <= window.innerWidth'), (width,filename)
            page.locator('img').evaluate_all('(images) => images.forEach(i => i.loading = "eager")')
            page.wait_for_function('Array.from(document.images).every(i => i.complete && i.naturalWidth > 0)')
            assert page.locator('img').evaluate_all('(images) => images.every(i => i.complete && i.naturalWidth > 0)')
        print(f'PASS: seven pages, images and no overflow at {width}px')
    for filename in ['cat-products.html','dog-products.html']:
        page.goto(f'http://127.0.0.1:8001/{filename}')
        expected = 8
        assert page.locator('.category-card').count() == expected
        assert page.locator('.sourcing-details').count() == expected
        page.locator('.sourcing-details summary').first.click()
        assert page.locator('.sourcing-details').first.get_attribute('open') is not None
        assert 'Product options:' in page.locator('.sourcing-details').first.inner_text()
    page.goto('http://127.0.0.1:8001/index.html')
    assert page.locator('.hero-pet').count() == 2
    assert page.locator('.service-matrix article').count() == 4
    assert page.locator('#pet-products .category-grid article').count() == 6
    assert page.locator('main > section').count() == 5
    page.evaluate('document.fonts.ready')
    assert page.evaluate("document.fonts.check('16px \"DM Sans\"')")
    assert page.evaluate("document.fonts.check('16px \"DM Serif Display\"')")
    page.locator('#pet-products article').first.get_by_role('link',name='Cat Products',exact=True).click()
    assert page.url.endswith('cat-products.html')
    print('PASS: equal cat/dog collections, sourcing details, homepage category navigation and local fonts')
    page.goto('http://127.0.0.1:8001/index.html')
    toggle=page.get_by_role('button',name='Menu')
    toggle.click(); assert toggle.get_attribute('aria-expanded')=='true'
    page.keyboard.press('Escape');assert toggle.get_attribute('aria-expanded')=='false'
    toggle.click();page.locator('#navigation').get_by_role('link',name='Cat Products',exact=True).click()
    page.locator('.category-card').first.get_by_role('link',name='Request Sourcing',exact=True).click()
    assert page.locator('#category').input_value()=='Cat Products'
    assert page.get_by_role('button',name='Send Sourcing Inquiry').is_disabled()
    assert page.locator('#submission-setup').is_visible()
    assert page.locator('#form-status').inner_text()==''
    assert page.locator('[name="company"]').get_attribute('required') is None
    assert page.locator('[name="details"]').get_attribute('required') is not None
    print('PASS: mobile menu, navigation, category mapping and disabled preview submission')
    page.set_viewport_size({'width':1440,'height':1000});page.goto('http://127.0.0.1:8001/');page.screenshot(path='/tmp/kova-home.png',full_page=True)
    page.screenshot(path='/tmp/kova-home-desktop.png')
    page.goto('http://127.0.0.1:8001/cat-products.html');page.screenshot(path='/tmp/kova-cats-desktop.png',full_page=True)
    page.set_viewport_size({'width':390,'height':844});page.goto('http://127.0.0.1:8001/');page.screenshot(path='/tmp/kova-home-mobile.png',full_page=True)
    assert not failed_assets, failed_assets
    assert not errors, errors
    print('PASS: no browser JavaScript errors')
    browser.close()
