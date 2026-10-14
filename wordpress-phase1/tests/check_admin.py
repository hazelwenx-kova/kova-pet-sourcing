"""Local authenticated ACF editor test; use an ephemeral test-only cookie file, never staging/live credentials."""
import json
from playwright.sync_api import sync_playwright
import os
cookie=json.load(open(os.environ['KOVA_ADMIN_COOKIE_FILE']))
BASE=os.getenv('WP_BASE','http://127.0.0.1:8100');HOME_ID=os.getenv('HOME_ID','70')
assert BASE.startswith(('http://127.0.0.1:','http://localhost:')), 'Local fixture only'
with sync_playwright() as p:
 b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox'])
 c=b.new_context(viewport={'width':1440,'height':1000},reduced_motion='reduce')
 c.add_cookies([{'name':cookie['name'],'value':cookie['cookie'],'url':BASE},{'name':cookie['auth_name'],'value':cookie['auth'],'url':BASE}])
 page=c.new_page();errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
 page.goto(BASE+'/wp-admin/post.php?post='+HOME_ID+'&action=edit',wait_until='networkidle')
 assert page.locator('.acf-field').count()>50
 page.locator('.acf-tab-button').filter(has_text='Page copy').first.click()
 headline=page.locator('.acf-field[data-name="text_12"] textarea')
 original=headline.input_value();headline.fill('Temporary dashboard edit')

 with page.expect_navigation(wait_until='networkidle'):page.locator('#publish').click()
 preview=c.new_page();preview.goto(BASE+'/',wait_until='networkidle');original_style=preview.locator('.hero-editorial-art .photo-tile').first.get_attribute('style');assert 'Temporary dashboard edit' in preview.locator('h1').inner_text()
 page.goto(BASE+'/wp-admin/post.php?post='+HOME_ID+'&action=edit',wait_until='networkidle')
 page.locator('.acf-tab-button').filter(has_text='Page copy').first.click()
 headline=next(x for x in page.locator('textarea').all() if x.input_value()=='Temporary dashboard edit');headline.fill(original)
 # Native ACF media selector: replace the first image, then restore via the same dialog.
 page.locator('.acf-tab-button').filter(has_text='Images').first.click()
 imgfield=page.locator('.acf-field[data-name="image_1"]')
 original_image=imgfield.locator('input[type=hidden]').input_value()
 imgfield.hover();imgfield.locator('[data-name="remove"]').click();imgfield.locator('[data-name="add"]').click()
 page.locator('.media-modal').wait_for();page.get_by_role('tab',name='Media Library',exact=True).click()
 # Select another existing, independent media image.
 items=page.locator('.media-modal .attachments .attachment')
 items.first.wait_for();selected=items.first.get_attribute('data-id')
 if selected==original_image:selected=items.nth(1).get_attribute('data-id');items.nth(1).click()
 else:items.first.click()
 page.locator('.media-modal .media-button-select').click();assert imgfield.locator('input[type=hidden]').input_value()==selected

 with page.expect_navigation(wait_until='networkidle'):page.locator('#publish').click()
 preview.goto(BASE+'/',wait_until='networkidle')
 changed=preview.locator('.hero-editorial-art .photo-tile').first.get_attribute('style')
 assert changed != original_style
 page.goto(BASE+'/wp-admin/post.php?post='+HOME_ID+'&action=edit',wait_until='networkidle')
 page.locator('.acf-tab-button').filter(has_text='Images').first.click()
 imgfield=page.locator('.acf-field[data-name="image_1"]');imgfield.hover();imgfield.locator('[data-name="remove"]').click();imgfield.locator('[data-name="add"]').click()
 page.get_by_role('tab',name='Media Library',exact=True).click();page.locator('.media-modal .attachment[data-id="'+original_image+'"]').click();page.locator('.media-modal .media-button-select').click()

 with page.expect_navigation(wait_until='networkidle'):page.locator('#publish').click()
 page.screenshot(path='/tmp/kova-theme-previews/admin-image-editor.png',full_page=True)
 assert not errors,errors
 print('PASS: ACF Free dashboard text save, native Media Library image replacement, restoration and admin JavaScript.')
 b.close()
