const fs = require('fs');
const path = require('path');
function walk(dir) {
  let results = [];
  const list = fs.readdirSync(dir);
  list.forEach(function(file) {
    file = path.join(dir, file);
    const stat = fs.statSync(file);
    if (stat && stat.isDirectory()) { 
      results = results.concat(walk(file));
    } else { 
      if (file.endsWith('.vue')) results.push(file);
    }
  });
  return results;
}
const files = walk('C:/Users/Ahmed/Desktop/PFE/project/Ticketing/frontend/src/views');
files.forEach(f => {
  let content = fs.readFileSync(f, 'utf8');
  if (content.includes('<main class="main">') && !content.includes('<AppHeader />')) {
    content = content.replace('<main class="main">', '<main class="main">\n      <AppHeader />');
    fs.writeFileSync(f, content);
    console.log('Updated', f);
  }
});
console.log('Done.');
