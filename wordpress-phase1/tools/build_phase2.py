"""Generate Phase 2 only; NEVER regenerate Phase 1 templates/field keys/data.
Requires BeautifulSoup4/Pillow. Static files and reference backups remain unchanged.
"""
from pathlib import Path
from bs4 import BeautifulSoup,NavigableString
from PIL import Image
import json
ROOT=Path(__file__).resolve().parents[2];BASE=ROOT/'wordpress-phase1';THEME=BASE/'kova-theme';DATA=BASE/'kova-content/data'
manifest={'version':2,'pages':{},'categories':[]}
images={};individual=THEME/'assets/images/individual';individual.mkdir(exist_ok=True)
for sheet in ['a','b']:
 im=Image.open(ROOT/'assets/images/dog-expansion'/('categories-'+sheet+'.webp'));w,h=im.size
 for n in range(4):
  x=n%2;y=n//2;name='dog-phase2-'+sheet+str(n)+'.webp'
  im.crop((round(x*w/2),round(y*h/2),round((x+1)*w/2),round((y+1)*h/2))).save(individual/name,quality=96)
  images['dog-panel-'+sheet+str(n)]='assets/images/individual/'+name
images['dog-materials-photo']='assets/images/dog-expansion/customisation.webp'
tokens={}
def php(s):
 t='KOVAPHP'+str(len(tokens))+'TOKEN';tokens[t]='<?php '+s+' ?>';return t
def dump(n):
 s=str(n)
 for a,b in tokens.items():s=s.replace(a,b)
 return s
for key,file,title in [('dog-products','dog-products.html','Dog Products'),('sourcing-services','sourcing-services.html','Sourcing Services'),('how-we-work','how-it-works.html','How We Work'),('about-kova','about.html','About KOVA'),('contact','contact.html','Contact')]:
 soup=BeautifulSoup((ROOT/file).read_text(),'html.parser');main=soup.main;fields={};idx=0
 if key=='dog-products':
  grid=main.select_one('.category-grid')
  for i,card in enumerate(grid.select('.category-card')):
   tile=card.select_one('.photo-tile');path=next(images[c] for c in tile['class'] if c in images)
   manifest['categories'].append({'key':'dog-'+str(i+1),'title':card.h3.get_text(),'description':card.select_one('.category-copy > p').get_text(),'options':card.select('dd')[0].get_text(),'customization':card.select('dd')[1].get_text(),'image':path,'alt':tile['aria-label'],'order':i+1,'species':'dog'})
  grid.clear();grid.append(NavigableString(php("kova_render_categories('dog');")))
 if key=='contact':
  # No non-functional HTML form. The real Fluent Forms shortcode owns validation/submission.
  form=main.select_one('form');container=soup.new_tag('div',attrs={'class':'kova-fluent-wrapper'});container.append(NavigableString(php('kova_render_inquiry_form();')));form.replace_with(container)
 for tile in main.select('.photo-tile'):
  idx+=1;field='image_'+str(idx);path=next(images[c] for c in reversed(tile['class']) if c in images)
  fields[field]={'label':'Image: '+tile.get('aria-label','')[:75],'type':'image','default':path,'alt':tile.get('aria-label',''),'size':'cover','position':'center'}
  tile['style']=php('echo esc_attr(kova_image_style('+json.dumps(field)+','+json.dumps(key)+'));')
  tile['aria-label']=php('echo esc_attr(kova_image_alt('+json.dumps(field)+','+json.dumps(key)+'));')
 for img in main.select('img'):
  idx+=1;field='image_'+str(idx);path=img['src'];alt=img.get('alt','')
  fields[field]={'label':'Image: '+alt[:75],'type':'image','default':path,'alt':alt,'size':'cover','position':'center'}
  img['src']=php('echo esc_url(kova_image_url('+json.dumps(field)+','+json.dumps(key)+'));')
  img['alt']=php('echo esc_attr(kova_image_alt('+json.dumps(field)+','+json.dumps(key)+'));')
 # Preserve inline emphasis/line breaks and each page-specific label. Do not edit numbers/decorative controls.
 for n in list(main.select('h1,h2,h3,p,summary,figcaption,.service-overview > span,.work-outcome,dt,dd')):
  if 'KOVAPHP' in n.decode_contents():continue
  for child in list(n.descendants):
   if isinstance(child,NavigableString) and child.strip() and child.parent.name not in ['svg','path'] and child.find_parent('a') is None:
    idx+=1;field='text_'+str(idx);value=str(child)
    fields[field]={'label':n.name.upper()+': '+value.strip()[:70],'type':'textarea','default':value}
    child.replace_with(php('echo kova_text('+json.dumps(field)+');'))
 for a in main.select('a[href]'):
  idx+=1;field='link_'+str(idx);url=a['href'];label=a.get_text(' ',strip=True)
  fields[field]={'label':'Destination: '+label,'type':'text','default':url,'instructions':'Use the existing site link, #section, or an HTTPS URL.'}
  a['href']=php('echo esc_url(kova_link('+json.dumps(field)+'));')
  for child in list(a.children):
   if isinstance(child,NavigableString) and child.strip():
    idx+=1;t='text_'+str(idx);fields[t]={'label':'Link label: '+label,'type':'text','default':str(child)};child.replace_with(php('echo kova_text('+json.dumps(t)+');'))
 manifest['pages'][key]={'title':title,'slug':key,'fields':fields,'html_class':' '.join(soup.html.get('class',[])),'description':soup.select_one('meta[name=description]')['content']}
 (THEME/'templates'/('page-'+key+'.php')).write_text('<?php /* Template Name: KOVA '+title+' */ defined("ABSPATH") || exit; get_header(); ?>\n'+dump(main)+'\n<?php get_footer(); ?>')
(DATA/'phase2-content.json').write_text(json.dumps(manifest,indent=2,ensure_ascii=False)+'\n')
print('Generated five Phase 2 templates, ACF schema and eight independent Dog category images. Phase 1 untouched.')
