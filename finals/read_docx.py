import zipfile
import xml.etree.ElementTree as ET
import sys
import os

def extract_text(path):
    try:
        with zipfile.ZipFile(path) as z:
            tree = ET.fromstring(z.read('word/document.xml'))
            ns = {'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'}
            
            paragraphs = []
            for p in tree.findall('.//w:p', ns):
                texts = [t.text for t in p.findall('.//w:t', ns) if t.text]
                if texts:
                    paragraphs.append(''.join(texts))
            return '\n'.join(paragraphs)
    except Exception as e:
        return str(e)

for path in sys.argv[1:]:
    text = extract_text(path)
    out_path = os.path.splitext(path)[0] + '.txt'
    with open(out_path, 'w', encoding='utf-8') as f:
        f.write(text)
    print(f"Saved {out_path}")
