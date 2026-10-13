"""Preview motion/accessibility checks; no production access or mail submissions."""
from playwright.sync_api import sync_playwright
from pathlib import Path
PAGES=['index.html','cat-products.html','dog-products.html','sourcing-services.html','how-it-works.html','about.html','contact.html']
BASE='http://127.0.0.1:8001/'
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path='/usr/bin/chromium',headless=True,args=['--no-sandbox'])
    page=browser.new_page(viewport={'width':1440,'height':1000})
    page.add_init_script("window.kovaShifts=[];new PerformanceObserver(list=>list.getEntries().forEach(e=>{if(!e.hadRecentInput)window.kovaShifts.push(e.value)})).observe({type:'layout-shift',buffered:true})")
    for filename in PAGES:
        page.goto(BASE+filename,wait_until='networkidle')
        links=page.locator('a[href]').evaluate_all('(links)=>links.map(a=>a.getAttribute("href"))')
        for href in links:
            if href.startswith('mailto:'):continue
            if href.startswith('#'):
                assert page.locator(href).count(),(filename,href)
            else:
                response=page.request.get(BASE+href)
                assert response.status==200,(filename,href,response.status)
        for section in page.locator('main > section').all():
            section.scroll_into_view_if_needed();page.wait_for_timeout(450)
            assert 'motion-reveal-pending' not in section.get_attribute('class')
        assert sum(page.evaluate('window.kovaShifts')) < .01,(filename,page.evaluate('window.kovaShifts'))
    page.goto(BASE+'cat-products.html',wait_until='networkidle')
    card=page.locator('.category-card').first
    card.hover();page.wait_for_timeout(250)
    assert card.evaluate('el=>getComputedStyle(el).transform')!='none'
    href=card.locator('a.category-image').get_attribute('href')
    card.locator('a.category-image').click();assert href in page.url
    assert page.locator('#send-inquiry').is_disabled()
    field=page.locator('[name="email"]');field.fill('invalid');field.blur()
    assert field.get_attribute('aria-invalid')=='true'
    assert page.locator('#feedback-email').is_visible()
    field.fill('review@example.com');assert field.get_attribute('aria-invalid') is None
    assert page.locator('#submission-setup').is_visible()
    page.goto(BASE+'how-it-works.html')
    step=page.locator('.work-timeline li').first;step.focus();assert step.get_attribute('tabindex')=='0'
    page.goto(BASE+'index.html')
    assert page.locator('.motion-reveal-pending').count()>0
    page.emulate_media(reduced_motion='reduce');page.wait_for_timeout(50)
    assert page.locator('.motion-reveal-pending').count()==0
    page.goto(BASE+'cat-products.html');card=page.locator('.category-card').first;card.hover()
    assert card.evaluate('el=>getComputedStyle(el).transform')=='none'
    assert page.locator('.photo-tile').first.evaluate('el=>getComputedStyle(el).transform')=='none'
    browser.close()
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path='/usr/bin/chromium',headless=True,args=['--no-sandbox'])
    context=browser.new_context(viewport={'width':390,'height':844},is_mobile=True,has_touch=True)
    page=context.new_page();page.goto(BASE+'index.html')
    page.get_by_role('button',name='Menu').tap()
    assert page.locator('.menu-toggle').get_attribute('aria-expanded')=='true'
    page.locator('#navigation').get_by_role('link',name='Cat Products',exact=True).tap()
    page.wait_for_url('**/cat-products.html')
    page.locator('.category-card').first.locator('a.category-image').tap()
    page.wait_for_url('**/contact.html*')
    assert 'contact.html' in page.url and page.locator('#send-inquiry').is_disabled()
    assert page.evaluate('document.documentElement.scrollWidth <= innerWidth')
    browser.close()
print('PASS: all seven link/anchor targets, scroll reveals, negligible animation CLS, hover/card destination, field feedback, process focus, reduced-motion changes and touch navigation; preview submission disabled')
