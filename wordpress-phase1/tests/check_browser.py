"""Test a local Phase 1 WP fixture against the unchanged static reference.
STATIC_BASE=http://127.0.0.1:8001 WP_BASE=http://127.0.0.1:8100 CAT_ID=79
Set those environment variables for another local test fixture. Requires Playwright.
"""
import os,json
from PIL import Image,ImageChops,ImageStat
from io import BytesIO
from pathlib import Path
from playwright.sync_api import sync_playwright
STATIC=os.getenv('STATIC_BASE','http://127.0.0.1:8001');WP=os.getenv('WP_BASE','http://127.0.0.1:8100');CAT=os.getenv('CAT_ID','79')
OUT=Path('/tmp/kova-theme-previews');OUT.mkdir(exist_ok=True)
with sync_playwright() as p:
 b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox'])
 results=[]
 for width,height in [(1440,1000),(768,1024),(390,844)]:
  context=b.new_context(viewport={'width':width,'height':height},reduced_motion='reduce')
  for file,url,name in [('index.html',WP+'/','home'),('cat-products.html',WP+'/?page_id='+CAT,'cat')]:
   ref=context.new_page();page=context.new_page();errors=[]
   page.on('pageerror',lambda e:errors.append(str(e)))
   ref.goto(STATIC+'/'+file,wait_until='networkidle');page.goto(url,wait_until='networkidle')
   assert 'KOVAPHP' not in page.locator('body').inner_text()
   normalize=lambda s:' '.join(s.split())
   assert normalize(ref.locator('main').inner_text())==normalize(page.locator('main').inner_text()),(name,width,'copy differs from static reference')
   for q in ['header.site-header','main','footer.site-footer','main > section','.category-grid','.category-card','h1','h2']:
    a=ref.locator(q).all();c=page.locator(q).all();assert len(a)==len(c),(name,width,q,len(a),len(c))
    for x,y in zip(a,c):
     ra=x.bounding_box();rb=y.bounding_box()
     for axis in ['x','y','width','height']:assert abs(ra[axis]-rb[axis])<1.1,(name,width,q,axis,ra,rb)
   # Compare the visible image tiles after collage separation as well as layout geometry.
   tile_diffs=[]
   for x,y in zip(ref.locator('.photo-tile').all(),page.locator('.photo-tile').all()):
    ia=Image.open(BytesIO(x.screenshot())).convert('RGB');ib=Image.open(BytesIO(y.screenshot())).convert('RGB')
    assert ia.size==ib.size
    difference=sum(ImageStat.Stat(ImageChops.difference(ia,ib)).mean)/3
    tile_diffs.append(difference);assert difference<6,(name,width,'image composition changed',difference)
   assert not errors,errors
   assert page.evaluate('document.documentElement.scrollWidth<=innerWidth'),(name,width,'overflow')
   backgrounds=page.locator('.photo-tile').evaluate_all('(els)=>els.map(el=>getComputedStyle(el).backgroundImage)')
   for bg in backgrounds:
    image=bg[5:-2];r=page.request.get(image);assert r.status==200,(image,r.status)
   if name=='cat':
    assert page.locator('.category-card').count()==8
    assert page.locator('details[open]').count()==0
    first=page.locator('summary').first;first.focus();page.keyboard.press('Enter');assert page.locator('details[open]').count()==1
    first.press('Enter');assert page.locator('details[open]').count()==0
   if width==390:
    page.locator('.menu-toggle').click();assert page.locator('#navigation').is_visible()
    page.keyboard.press('Escape');assert page.locator('.menu-toggle').get_attribute('aria-expanded')=='false'
   page.evaluate('document.activeElement.blur()')
   page.screenshot(path=str(OUT/(name+'-'+str(width)+'.png')),full_page=True)
   results.append({'page':name,'width':width,'geometry':'matches reference within 1.1px'})
   ref.close();page.close()
  context.close()
 page=b.new_page()
 page.goto(WP+'/?page_id='+CAT)
 page.locator('.category-image').first.click();page.wait_for_url('**/reference/contact.html?category=*')
 assert page.locator('#send-inquiry').is_disabled();assert page.locator('#submission-setup').is_visible()
 for file in ['dog-products.html','sourcing-services.html','how-it-works.html','about.html','contact.html']:
  r=page.request.get(WP+'/wp-content/themes/kova-theme/reference/'+file);assert r.status==200
 page.goto(WP+'/');page.locator('.motion-reveal-pending').first.scroll_into_view_if_needed();page.wait_for_timeout(500)
 assert 'motion-reveal-pending' not in page.locator('main>section').nth(1).get_attribute('class')
 page.emulate_media(reduced_motion='reduce');page.wait_for_timeout(100);assert page.locator('.motion-reveal-pending').count()==0
 b.close()
 print(json.dumps(results,indent=2));print('PASS: geometry, assets, accordion keyboard, mobile menu, JS, reference links, disabled contact and motion preferences.')
