"""Local browser comparisons, including image cropping and all final navigation targets."""
import os,json
from pathlib import Path
from io import BytesIO
from PIL import Image,ImageChops,ImageStat
from playwright.sync_api import sync_playwright
STATIC=os.getenv('STATIC_BASE','http://127.0.0.1:8001');WP=os.getenv('WP_BASE','http://127.0.0.1:8101')
assert WP.startswith(('http://127.0.0.1:','http://localhost:')),'Local fixture only'
OUT=Path('/tmp/kova-phase2-previews');OUT.mkdir(exist_ok=True)
PAGES=[('index.html','/','home'),('cat-products.html','/cat-products/','cat'),('dog-products.html','/dog-products/','dog'),('sourcing-services.html','/sourcing-services/','services'),('how-it-works.html','/how-we-work/','process'),('about.html','/about-kova/','about'),('contact.html','/contact/','contact')]
with sync_playwright() as p:
 b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox'])
 results=[]
 for width,height in [(1440,1000),(768,1024),(390,844)]:
  c=b.new_context(viewport={'width':width,'height':height},reduced_motion='reduce')
  for file,path,name in PAGES:
   ref=c.new_page();page=c.new_page();errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
   assert page.goto(WP+path,wait_until='networkidle').status==200
   ref.goto(STATIC+'/'+file,wait_until='networkidle')
   assert 'KOVAPHP' not in page.locator('body').inner_text()
   normalize=lambda s:' '.join(s.split())
   if name!='contact':
    assert normalize(ref.locator('main').inner_text())==normalize(page.locator('main').inner_text()),(name,width,'copy')
    for selector in ['header.site-header','main','footer.site-footer','main > section','.category-grid','.category-card','h1','h2']:
     a=ref.locator(selector).all();d=page.locator(selector).all();assert len(a)==len(d),(name,selector)
     for x,y in zip(a,d):
      ra=x.bounding_box();rb=y.bounding_box()
      for axis in ['x','y','width','height']:assert abs(ra[axis]-rb[axis])<1.1,(name,width,selector,axis,ra,rb)
   else:
    for selector in ['header.site-header','main > section:first-child','.contact-layout aside']:
     ra=ref.locator(selector).bounding_box();rb=page.locator(selector).bounding_box()
     for axis in (['x','y','width'] if selector=='.contact-layout aside' else ['x','y','width','height']):assert abs(ra[axis]-rb[axis])<1.1,(name,width,selector,axis)
    assert normalize(ref.locator('.contact-layout aside').inner_text())==normalize(page.locator('.contact-layout aside').inner_text())
    assert page.locator('#inquiry-form').count()==0
    assert page.locator('.kova-fluent-wrapper form').count()==0
    assert page.locator('.submission-setup').is_visible()
   for x,y in zip(ref.locator('.photo-tile,main img').all(),page.locator('.photo-tile,main img').all()):
    ia=Image.open(BytesIO(x.screenshot())).convert('RGB');ib=Image.open(BytesIO(y.screenshot())).convert('RGB')
    assert ia.size==ib.size,(name,width,'image size')
    difference=sum(ImageStat.Stat(ImageChops.difference(ia,ib)).mean)/3
    assert difference<6,(name,width,'crop changed',difference)
   assert page.evaluate('document.documentElement.scrollWidth<=innerWidth'),(name,width,'overflow')
   assert not errors,errors
   for link in page.locator('a[href]').all():
    href=link.get_attribute('href');assert '/reference/' not in href,(name,href)
    if href.startswith('mailto:'):continue
    if href.startswith('#'):assert page.locator(href).count(),href
    elif href.startswith(WP):assert page.request.get(href).status==200,(name,href)
   if name in ['dog','cat']:
    assert page.locator('.category-card').count()==8 and page.locator('details[open]').count()==0
    s=page.locator('summary').first;s.focus();s.press('Enter');assert page.locator('details[open]').count()==1;s.press('Enter')
   if width==390:
    toggle=page.locator('.menu-toggle');toggle.click();assert page.locator('#navigation').is_visible();page.keyboard.press('Escape');assert toggle.get_attribute('aria-expanded')=='false'
   page.evaluate('document.activeElement.blur()');page.screenshot(path=str(OUT/(name+'-'+str(width)+'.png')),full_page=True)
   results.append({'page':name,'width':width,'layout':'matches static within 1.1px' if name!='contact' else 'outer design preserved; unconfigured real-form slot'})
   ref.close();page.close()
  c.close()
 page=b.new_page(viewport={'width':1440,'height':1000})
 page.goto(WP+'/dog-products/');card=page.locator('.category-card').first;card.hover();page.wait_for_timeout(400)
 assert card.evaluate('el=>getComputedStyle(el).transform')!='none'
 assert card.locator('.photo-tile').evaluate('el=>getComputedStyle(el).transform')!='none'
 card.locator('a.category-image').click();page.wait_for_url('**/contact/**');assert 'Dog' in page.url
 page.goto(WP+'/how-we-work/');step=page.locator('.work-timeline li').first;step.focus();assert step.get_attribute('tabindex')=='0'
 page.goto(WP+'/');assert page.locator('.motion-reveal-pending').count()>0
 page.locator('.motion-reveal-pending').first.scroll_into_view_if_needed();page.wait_for_timeout(500)
 page.emulate_media(reduced_motion='reduce');page.wait_for_timeout(100);assert page.locator('.motion-reveal-pending').count()==0
 b.close();print(json.dumps(results,indent=2));print('PASS: seven pages/three viewports, copy, geometry, Dog crop, assets, internal links, keyboards/mobile, motion and honest Contact integration placeholder.')
