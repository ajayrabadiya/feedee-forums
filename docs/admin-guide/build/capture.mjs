import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import puppeteer from 'puppeteer';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const shotDir = path.resolve(__dirname, '../screenshots');
const base = 'http://127.0.0.1:8001';
const email = 'you@example.com';
const password = 'GuideCapture-Temp-2026!';

fs.mkdirSync(shotDir, { recursive: true });

async function redactPii(page) {
  await page.evaluate(() => {
    const mask = (text) => {
      if (!text) return text;
      return text
        .replace(/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/gi, 'member@example.com')
        .replace(/@\w+/g, '@member');
    };

    document.querySelectorAll('a[href^="mailto:"], .cell-email, td, small, strong, span, p').forEach((el) => {
      if (el.children.length === 0 && /@/.test(el.textContent || '')) {
        el.textContent = mask(el.textContent);
      }
    });

    document.querySelectorAll('input[type="email"], input[name*="mail"], input[name*="username"], input[type="password"]').forEach((el) => {
      if (el.type === 'password') {
        el.value = '';
        el.placeholder = '••••••••';
      } else if (el.value && el.value.includes('@')) {
        el.value = 'admin@example.com';
      }
    });

    document.querySelectorAll('.admin-user .who strong').forEach((el) => {
      el.textContent = 'Administrator';
    });
  });
}

async function shot(page, name, options = {}) {
  await redactPii(page);
  await page.waitForTimeout?.(300);
  await new Promise((r) => setTimeout(r, 400));
  const file = path.join(shotDir, `${name}.png`);
  await page.screenshot({
    path: file,
    type: 'png',
    fullPage: Boolean(options.fullPage),
    clip: options.clip,
  });
  console.log('saved', name);
}

const browser = await puppeteer.launch({
  headless: true,
  defaultViewport: { width: 1440, height: 900, deviceScaleFactor: 1 },
  args: ['--no-sandbox', '--disable-dev-shm-usage'],
});

const page = await browser.newPage();

try {
  // Login page
  await page.goto(`${base}/login`, { waitUntil: 'networkidle2', timeout: 60000 });
  await shot(page, '01-login');

  await page.type('#email', email, { delay: 10 });
  await page.type('#password', password, { delay: 10 });
  await Promise.all([
    page.click('button[type="submit"].btn-fill, form button[type="submit"]'),
    page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 60000 }),
  ]);

  // Ensure admin area
  await page.goto(`${base}/admin`, { waitUntil: 'networkidle2', timeout: 60000 });
  if (!page.url().includes('/admin')) {
    throw new Error(`Login did not reach admin. At ${page.url()}`);
  }
  await shot(page, '02-dashboard', { fullPage: true });
  await shot(page, '02-dashboard-top', { clip: { x: 0, y: 0, width: 1440, height: 820 } });

  await page.goto(`${base}/admin/customers`, { waitUntil: 'networkidle2', timeout: 60000 });
  await shot(page, '03-customers', { clip: { x: 0, y: 0, width: 1440, height: 900 } });

  await page.goto(`${base}/admin/posts`, { waitUntil: 'networkidle2', timeout: 60000 });
  await shot(page, '04-posts', { clip: { x: 0, y: 0, width: 1440, height: 900 } });

  await page.goto(`${base}/admin/contacts`, { waitUntil: 'networkidle2', timeout: 60000 });
  await shot(page, '05-contacts', { clip: { x: 0, y: 0, width: 1440, height: 900 } });

  // Open first contact if available
  const contactHref = await page.$eval('a.cell-message', (a) => a.getAttribute('href')).catch(() => null);
  if (contactHref) {
    await page.goto(new URL(contactHref, base).href, { waitUntil: 'networkidle2', timeout: 60000 });
    await shot(page, '06-contact-detail', { clip: { x: 0, y: 0, width: 1440, height: 900 } });
  }

  await page.goto(`${base}/admin/settings`, { waitUntil: 'networkidle2', timeout: 60000 });
  await shot(page, '07-settings', { clip: { x: 0, y: 0, width: 1440, height: 900 } });

  await page.goto(`${base}/admin/support-links`, { waitUntil: 'networkidle2', timeout: 60000 });
  await shot(page, '08-support-analytics', { clip: { x: 0, y: 0, width: 1440, height: 900 } });

  await page.goto(`${base}/`, { waitUntil: 'networkidle2', timeout: 60000 });
  await shot(page, '09-public-home-support', { clip: { x: 0, y: 0, width: 1440, height: 900 } });

  // Logout menu on admin
  await page.goto(`${base}/admin`, { waitUntil: 'networkidle2', timeout: 60000 });
  await page.click('.admin-user');
  await new Promise((r) => setTimeout(r, 400));
  await redactPii(page);
  await shot(page, '10-admin-menu', { clip: { x: 980, y: 0, width: 460, height: 320 } });

  console.log('CAPTURE_OK');
} catch (error) {
  console.error('CAPTURE_FAIL', error);
  process.exitCode = 1;
} finally {
  await browser.close();
}
