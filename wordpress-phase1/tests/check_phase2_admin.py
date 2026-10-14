"""Actual ACF admin text/image editing on temporary local-only pages, deleted afterward."""
import os,json,subprocess
from pathlib import Path
from playwright.sync_api import sync_playwright
WP=os.getenv('WP_BASE','http://127.0.0.1:8101');assert WP.startswith(('http://127.0.0.1:','http://localhost:'))
PHP=os.environ['KOVA_PHP'];script=Path(__file__).with_name('admin_phase2_fixture.php')
fixture=json.loads(subprocess.check_output([PHP,str(script),'create'],env=os.environ))
try:
 with sync_playwright() as p:
  b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox'])
  c=b.new_context(viewport={'width':1440,'height':1000},reduced_motion='reduce')
  c.add_cookies([{'name':fixture['name'],'value':fixture['cookie'],'url':WP},{'name':fixture['auth_name'],'value':fixture['auth'],'url':WP}])
  page=c.new_page();errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
  for key,id in fixture['ids'].items():
   page.goto(WP+f'/wp-admin/post.php?post={id}&action=edit',wait_until='networkidle')
   page.locator('.acf-tab-button').filter(has_text='Page copy').first.click()
   field=page.locator('.acf-field[data-name^="text_"] textarea').first
   value='Local ACF editor verification '+key;field.fill(value)
   with page.expect_navigation(wait_until='networkidle'):page.locator('#save-post').click()
   preview=c.new_page();preview.goto(WP+f'/?page_id={id}&preview=true',wait_until='networkidle')
   assert value in preview.locator('main').inner_text(),key
   if key!='contact':
    visual=preview.locator('.dog-materials-photo' if key=='dog-products' else 'main img').first
    before_visual=visual.get_attribute('style' if key=='dog-products' else 'src')
    page.locator('.acf-tab-button').filter(has_text='Images').first.click()
    image=page.locator('.acf-field[data-type="image"]').first
    image.locator('[data-name="add"]').click();page.get_by_role('tab',name='Media Library',exact=True).click()
    item=page.locator('.media-modal .attachment').first;item.wait_for();mediaid=item.get_attribute('data-id');item.click()
    page.locator('.media-modal .media-button-select').click()
    assert image.locator('input[type=hidden]').input_value()==mediaid
    with page.expect_navigation(wait_until='networkidle'):page.locator('#save-post').click()
    preview.reload(wait_until='networkidle')
    assert preview.locator('main img,.dog-materials-photo').first.is_visible(),key
    after_visual=preview.locator('.dog-materials-photo' if key=='dog-products' else 'main img').first.get_attribute('style' if key=='dog-products' else 'src')
    assert after_visual != before_visual,(key,'media replacement did not reach frontend')
   else:
    assert page.locator('.acf-field[data-name="kova_fluent_form_id"]').count()==1
    assert preview.locator('.kova-fluent-wrapper form').count()==0
   preview.close()
   print('PASS: actual ACF editor text/image save — '+key)
  assert not errors,errors;b.close()
finally:subprocess.check_call([PHP,str(script),'cleanup'],env=os.environ)
