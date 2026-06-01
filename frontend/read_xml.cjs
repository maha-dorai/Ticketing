const fs = require('fs');
const xml = fs.readFileSync('./cahier_extracted/word/document.xml', 'utf8');
const text = xml.replace(/<\/w:p>/g, '\n').replace(/<[^>]+>/g, '');
fs.writeFileSync('./extracted_text.txt', text);