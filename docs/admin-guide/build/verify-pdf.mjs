import fs from 'fs';
import path from 'path';
import { fileURLToPath, pathToFileURL } from 'url';
import puppeteer from 'puppeteer';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const pdfPath = path.resolve(__dirname, '../../Feedee-Forums-Admin-User-Guide.pdf');
const outDir = path.resolve(__dirname, '../verify-pages');
fs.mkdirSync(outDir, { recursive: true });

const browser = await puppeteer.launch({
  headless: true,
  args: ['--no-sandbox', '--disable-dev-shm-usage'],
});
const page = await browser.newPage();
await page.setViewport({ width: 1240, height: 1754, deviceScaleFactor: 1 });

// Use Chrome PDF viewer via file URL — better: convert with pdf.js not available.
// Fallback: open HTML and screenshot each .page section for visual QA (source of PDF).
const htmlPath = path.resolve(__dirname, '../guide.html');
await page.goto(pathToFileURL(htmlPath).href, { waitUntil: 'networkidle0' });

const count = await page.$$eval('.page', (els) => els.length);
for (let i = 0; i < count; i++) {
  const handle = (await page.$$('.page'))[i];
  await handle.screenshot({ path: path.join(outDir, `page-${String(i + 1).padStart(2, '0')}.png`) });
}

const pdfStat = fs.statSync(pdfPath);
console.log(JSON.stringify({
  pdfBytes: pdfStat.size,
  pdfExists: true,
  htmlSections: count,
  verifyDir: outDir,
}, null, 2));

await browser.close();
