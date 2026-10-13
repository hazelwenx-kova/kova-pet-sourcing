"""Build native Elementor 0.4 page templates from the unchanged static reference.
Image URLs intentionally remain blank: assign the supplied assets in Media Library.
No staging/live credentials or requests are used.
"""
from pathlib import Path
from lxml import html,etree
import hashlib,json,re
ROOT=Path(__file__).resolve().parents[2]
OUT=ROOT/'wordpress-migration'
LINKS={'index.html':'/','cat-products.html':'/cat-products/','dog-products.html':'/dog-products/','sourcing-services.html':'/sourcing-services/','how-it-works.html':'/how-we-work/','about.html':'/about-kova/','contact.html':'/contact/'}
media=[]
def map_url(url):
 base,sep,query=url.partition('?')
 return LINKS.get(base,base)+(sep+query if sep else '')
class Builder:
 def __init__(self,page):self.page=page;self.counter=0
 def uid(self):
  self.counter+=1;return hashlib.sha256(f'{self.page}:{self.counter}'.encode()).hexdigest()[:7]
 def container(self,classes,children,title='',css_id=''):
  settings={'content_width':'full','flex_direction':'column','flex_gap':{'column':'0','row':'0','isLinked':True,'unit':'px','size':0},'padding':{'unit':'px','top':'0','right':'0','bottom':'0','left':'0','isLinked':True},'css_classes':classes,'_title':title or classes or 'Content group'}
  if css_id:settings['_element_id']=css_id
  return {'id':self.uid(),'elType':'container','settings':settings,'elements':children,'isInner':True}
 def widget(self,kind,settings,classes='',title='',children=None):
  settings.update({'_css_classes':classes,'_title':title or kind,'_margin':{'unit':'px','top':'0','right':'0','bottom':'0','left':'0','isLinked':True}})
  return {'id':self.uid(),'elType':'widget','widgetType':kind,'settings':settings,'elements':children or [],'isInner':False}
 def copy(self,content,classes='',title=''):
  content=re.sub(r'href="([^"]+)"',lambda m:'href="'+map_url(m.group(1))+'"',content)
  return self.widget('text-editor',{'editor':content},'kova-copy '+classes,title)
 def image(self,asset,alt,classes=''):
  node=self.widget('image',{'image':{'id':0,'url':''},'image_size':'full','caption_source':'none','link_to':'none'},'kova-picture '+classes,'图片：'+asset+'.webp')
  media.append({'page':self.page,'element_id':node['id'],'file':asset+'.webp','alt':alt,'editor_label':node['settings']['_title']})
  return node
 def convert(self,e):
  tag=e.tag;classes=e.get('class','')
  if not isinstance(tag,str) or tag in ['svg','path']:return None
  if 'photo-tile' in classes.split():
   if 'editorial-cat' in classes:asset='hero-cat'
   elif 'dog-panel-0' in classes:asset='hero-dog'
   elif 'dog-panel-1' in classes:asset='dog-walking'
   elif 'home-toys' in classes:asset='home-toys'
   elif 'cat-new-litter' in classes:asset='cat-litter'
   elif 'cat-new-collars' in classes:asset='cat-collars'
   elif 'cat-new-materials' in classes:asset='materials'
   elif 'cat-new-packaging' in classes:asset='packaging'
   else:
    panel=next(c for c in classes.split() if re.fullmatch('panel-[0-5]',c))
    asset=['cat-beds','cat-trees','cat-toys','feeding','cat-travel','grooming'][int(panel[-1])]
   return self.image(asset,e.get('aria-label','Concept image'),classes.replace('photo-tile',''))
  if tag in ['h1','h2','h3','h4']:
   inner=(e.text or '')+''.join(etree.tostring(c,encoding='unicode',method='html') for c in e)
   return self.widget('heading',{'title':inner,'header_size':tag},'kova-heading '+classes,e.text_content()[:65])
  if tag in ['p','span','small']:
   if classes in ['image-arrow','options-indicator']:return None
   content=etree.tostring(e,encoding='unicode',method='html',with_tail=False)
   if tag=='span':content='<p>'+content+'</p>'
   return self.copy(content,title=e.text_content()[:65])
  if tag=='a':
   href=e.get('href','');base,sep,query=href.partition('?')
   url=LINKS.get(base,base)+(sep+query if sep else '')
   photos=e.xpath('.//*[contains(@class, "photo-tile")]')
   if photos:
    picture=self.convert(photos[0]);picture['settings'].update({'link_to':'custom','link':{'url':url,'is_external':'','nofollow':''}})
    return self.container(classes,[picture],'Category image link')
   return self.widget('button',{'text':e.text_content().strip(),'link':{'url':url,'is_external':'','nofollow':''},'size':'sm'},'kova-link '+classes if 'text-link' in classes or not classes else 'kova-button '+classes,e.text_content()[:65])
  if tag=='details':
   d=e.xpath('./dl')[0];children=[]
   for field in d:
    if field.tag=='dt':children.append(self.widget('heading',{'title':field.text_content(),'header_size':'h4'},'kova-option-label'))
    else:children.append(self.copy('<p>'+field.text_content()+'</p>'))
   inner=self.container('kova-option-content',children,'Options content');inner['isLocked']=True
   item_id=self.uid()
   return self.widget('nested-accordion',{'items':[{'_id':item_id,'item_title':'Explore sourcing options','element_css_id':''}],'default_state':'all_collapsed','max_items_expended':'one','faq_schema':'','accordion_item_title_icon':{'value':'fas fa-plus','library':'fa-solid'},'accordion_item_title_icon_active':{'value':'fas fa-minus','library':'fa-solid'}},'kova-options','Explore sourcing options',[inner])
  children=[n for c in e if (n:=self.convert(c)) is not None]
  if tag=='article':classes+=' kova-article'
  if tag=='li':classes+=' kova-list-item'
  if tag=='nav':classes+=' kova-page-nav'
  return self.container(classes.strip(),children,(e.xpath('.//h2/text()') or [classes])[0],e.get('id',''))
for filename,page,rootclass in [('index.html','Home','consultancy-home'),('cat-products.html','Cat Products','cat-sourcing-page')]:
 b=Builder(page);doc=html.parse(str(ROOT/filename));sections=[b.convert(e) for e in doc.xpath('//main/*')]
 node=b.container('kova-wp-page '+rootclass,sections,'KOVA '+page+' — editable page content');node['isInner']=False
 template={'content':[node],'page_settings':{'hide_title':'yes'},'version':'0.4','title':'KOVA — '+page+' — Phase 1','type':'page'}
 slug='home' if page=='Home' else 'cat-products'
 (OUT/'templates'/f'kova-{slug}.json').write_text(json.dumps(template,ensure_ascii=False,indent=2)+'\n')
(OUT/'assets/media-manifest.json').write_text(json.dumps(media,ensure_ascii=False,indent=2)+'\n')
print('Built Home and Cat Products: native containers/widgets; 18 Media Library image slots, 14 independent files.')
