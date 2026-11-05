import path from 'path';
import { fileURLToPath, pathToFileURL } from 'url';
import puppeteer from 'puppeteer';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const htmlPath = path.resolve(__dirname, '../guide.html');
const pdfPath = path.resolve(__dirname, '../../Feedee-Forums-Admin-User-Guide.pdf');

const browser = await puppeteer.launch({
  headless: true,
  args: ['--no-sandbox', '--disable-dev-shm-usage'],
});

const page = await browser.newPage();
await page.goto(pathToFileURL(htmlPath).href, { waitUntil: 'networkidle0', timeout: 120000 });
await page.pdf({
  path: pdfPath,
  format: 'A4',
  printBackground: true,
  displayHeaderFooter: true,
  headerTemplate: '<div></div>',
  footerTemplate: `
    <div style="width:100%;font-size:9px;color:#777;padding:0 16mm;display:flex;justify-content:space-between;font-family:Segoe UI,Arial,sans-serif;">
      <span>Feedee Forums · Admin Panel User Guide</span>
      <span>https://feedeeforums.com/</span>
      <span>Page <span class="pageNumber"></span> of <span class="totalPages"></span></span>
    </div>
  `,
  margin: { top: '14mm', right: '14mm', bottom: '18mm', left: '14mm' },
});

const pages = await page.evaluate(() => {
  const sections = [...document.querySelectorAll('.page')];
  return {
    sectionCount: sections.length,
    images: [...document.images].map((img) => ({
      src: img.getAttribute('src'),
      naturalWidth: img.naturalWidth,
      complete: img.complete,
    })),
  };
});

console.log(JSON.stringify({ pdfPath, ...pages }, null, 2));
await browser.close();
