"""Build standard WordPress upload ZIPs, excluding development and generated caches."""
from pathlib import Path
from zipfile import ZipFile,ZIP_DEFLATED
base=Path(__file__).resolve().parents[1];out=base/'packages';out.mkdir(exist_ok=True)
for name in ['kova-theme','kova-content']:
 with ZipFile(out/(name+'.zip'),'w',ZIP_DEFLATED) as z:
  for p in sorted((base/name).rglob('*')):
   if p.is_file() and '__pycache__' not in p.parts:z.write(p,p.relative_to(base))
 print(out/(name+'.zip'))
