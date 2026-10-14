"""Generate fixed PHP templates and initial data from the approved static source.
Development only: PYTHONPATH=/tmp/kova-build-libs python wordpress-phase1/tools/build_reference.py
Requires beautifulsoup4 and Pillow. Never changes static reference files.
"""
from pathlib import Path
from bs4 import BeautifulSoup, NavigableString
from PIL import Image
import json, shutil, re
ROOT=Path(__file__).resolve().parents[2]; OUT=ROOT/'wordpress-phase1'; THEME=OUT/'kova-theme'; DATA=OUT/'kova-content/data'
shutil.copytree(ROOT/'assets',THEME/'assets',dirs_exist_ok=True)
for license in (THEME/'assets/fonts').glob('*LICENSE.txt'):license.write_text('\n'.join(line.rstrip() for line in license.read_text().splitlines())+'\n')
IMG=THEME/'assets/images/individual';IMG.mkdir(exist_ok=True)
def crop(source,cols,rows,n,name):
 im=Image.open(ROOT/'assets/images'/source);w,h=im.size;x=n%cols;y=n//cols
 im.crop((round(x*w/cols),round(y*h/rows),round((x+1)*w/cols),round((y+1)*h/rows))).save(IMG/(name+'.webp'),quality=96)
 return 'assets/images/individual/'+name+'.webp'
images={}
for i in range(6): images['panel-'+str(i)]=crop('category-concepts.webp',3,2,i,'cat-'+str(i+1))
for i in range(4):images['dog-panel-'+str(i)]=crop('dog-concepts.webp',2,2,i,'dog-'+str(i+1))
for i,c in enumerate(['cat-new-litter','cat-new-collars','cat-new-materials','cat-new-packaging']):images[c]=crop('cat-expansion/concepts.webp',2,2,i,c)
images['editorial-cat']='assets/images/hero-concept.webp';images['home-toys']='assets/images/home-toys.webp'
manifest={'version':1,'pages':{},'categories':[],'global':{}}
# Insert PHP tokens after parsing so BeautifulSoup does not escape PHP attributes.
tokens={}
def php(s):
 t='KOVAPHP'+str(len(tokens))+'TOKEN';tokens[t]='<?php '+s+' ?>';return t
def dump(node):
 s=str(node)
 for a,b in tokens.items():s=s.replace(a,b)
 return s
# Shared templates preserve original markup; navigation rendered separately.
source=BeautifulSoup((ROOT/'index.html').read_text(),'html.parser')
header=source.select_one('header.site-header');header.select_one('nav').clear();header.select_one('nav').append(NavigableString(php('kova_navigation();')))
header.select_one('a.logo')['href']=php("echo esc_url(home_url('/'));")
header.select_one('a.logo').attrs.pop('aria-current',None)
top=source.select_one('.top-note');skip=source.select_one('.skip')
(THEME/'header.php').write_text('''<?php defined('ABSPATH') || exit; ?><!doctype html>
<html <?php language_attributes(); ?> class="<?php echo esc_attr(kova_layout_class()); ?>"><head>
<meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" href="<?php echo esc_url(get_template_directory_uri().'/assets/favicon.svg'); ?>" type="image/svg+xml">
<?php wp_head(); ?></head><body <?php body_class(); ?>><?php wp_body_open(); ?>
'''+dump(skip)+dump(top)+dump(header)+'\n')
footer=source.select_one('footer');footer.select_one('a.logo')['href']=php("echo esc_url(home_url('/'));")
footer.select_one('nav').clear();footer.select_one('nav').append(NavigableString(php("kova_navigation('footer');")))
for i,n in enumerate(footer.select('p,.footer-bottom > span:last-child')):
 if n.find('a') or n.find(id='year'):continue
 key='footer_'+str(i+1);value=n.decode_contents();manifest['global'][key]={'label':n.get_text(' ',strip=True)[:65],'type':'textarea','default':value};n.clear();n.append(NavigableString(php('echo kova_text('+json.dumps(key)+',0);')))
(THEME/'footer.php').write_text(dump(footer)+'\n<?php wp_footer(); ?></body></html>')
for page,file in [('home','index.html'),('cat-products','cat-products.html')]:
 soup=BeautifulSoup((ROOT/file).read_text(),'html.parser');main=soup.main
 fields={};imagefields={};idx=0
 if page=='cat-products':
  grid=main.select_one('.category-grid')
  for i,card in enumerate(grid.select('.category-card')):
   tile=card.select_one('.photo-tile');path=next(images[c] for c in tile['class'] if c in images)
   manifest['categories'].append({'key':'cat-'+str(i+1),'title':card.h3.get_text(),'description':card.select_one('.category-copy > p').get_text(),'options':card.select('dd')[0].get_text(),'customization':card.select('dd')[1].get_text(),'image':path,'alt':tile['aria-label'],'order':i+1,'species':'cat'})
  grid.clear();grid.append(NavigableString(php("kova_render_categories('cat');")))
 for tile in main.select('.photo-tile'):
  idx+=1;key='image_'+str(idx);path=next(images[c] for c in reversed(tile['class']) if c in images)
  imagefields[key]={'label':'Image: '+tile.get('aria-label','')[:75],'type':'image','default':path,'alt':tile.get('aria-label',''),'position':'74% center' if 'editorial-cat' in tile['class'] else 'center','size':'cover' if any(c in tile['class'] for c in ['editorial-cat','home-toys']) else '100% 100%'}
  tile['style']=php('echo esc_attr(kova_image_style('+json.dumps(key)+','+json.dumps(page)+'));')
  tile['aria-label']=php('echo esc_attr(kova_image_alt('+json.dumps(key)+','+json.dumps(page)+'));')
 # Main content leaf units only. Keep inline br/em/strong and SVG outside labels.
 candidates=list(main.select('h1,h2,h3,p,summary,figcaption,.hero-scope > span,.hero-pet > span,.experience-number'))
 for n in candidates:
  if 'KOVAPHP' in n.decode_contents():continue
  # Edit plain text segments; fixed <br>/<em>/links remain in the PHP template.
  for child in list(n.descendants):
   if isinstance(child,NavigableString) and child.strip() and child.parent.name not in ['svg','path'] and child.find_parent('a') is None:
    idx+=1;key='text_'+str(idx);val=str(child)
    fields[key]={'label':n.name.upper()+': '+val.strip()[:70],'type':'textarea','default':val}
    child.replace_with(php('echo kova_text('+json.dumps(key)+');'))
 for a in main.select('a[href]'):
  idx+=1;key='link_'+str(idx);url=a['href'];label=a.get_text(' ',strip=True)
  fields[key]={'label':'Destination: '+label,'type':'text','default':url,'instructions':'Use a site page, #section, relative URL or HTTPS link. Keep section links unchanged unless needed.'}
  a['href']=php('echo esc_url(kova_link('+json.dumps(key)+'));')
  # Separate text label from existing arrow SVG.
  for child in list(a.children):
   if isinstance(child,NavigableString) and child.strip():
    idx+=1;tkey='text_'+str(idx);fields[tkey]={'label':'Link label: '+label,'type':'text','default':str(child)};child.replace_with(php('echo kova_text('+json.dumps(tkey)+');'))
 fields.update(imagefields)
 manifest['pages'][page]={'title':'Home' if page=='home' else 'Cat Products','fields':fields,'html_class':' '.join(soup.html.get('class',[])), 'description':soup.select_one('meta[name=description]')['content']}
 (THEME/'templates'/('page-'+page+'.php')).write_text('<?php /* Template Name: KOVA '+('Home' if page=='home' else 'Cat Products')+' */ defined("ABSPATH") || exit; get_header(); ?>\n'+dump(main)+'\n<?php get_footer(); ?>')
# Other five pages are static reference files, not migrated editable WP pages.
ref=THEME/'reference';ref.mkdir(exist_ok=True)
for f in ['dog-products.html','sourcing-services.html','how-it-works.html','about.html','contact.html']:
 s=(ROOT/f).read_text().replace('"assets/','"../assets/')
 # Home/Cat routing for a WP installed in a subdirectory as well as root: PHP integration sets links later.
 s=s.replace('href="index.html"','href="../../../../?kova_page=home"').replace('href="cat-products.html"','href="../../../../?kova_page=cat-products"')
 # Remove production canonical/social URLs from local reference copies.
 soup=BeautifulSoup(s,'html.parser')
 for n in soup.select('link[rel=canonical],meta[property="og:url"]'):n.decompose()
 (ref/f).write_text(str(soup))
(DATA/'initial-content.json').write_text(json.dumps(manifest,indent=2,ensure_ascii=False)+'\n')
print('Generated Home/Cat fixed templates, '+str(len(manifest['categories']))+' categories, independent image assets and editable field definitions.')
