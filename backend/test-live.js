const puppeteer = require('puppeteer');

(async () => {
  const browser = await puppeteer.launch();
  const page = await browser.newPage();
  
  page.on('console', msg => console.log('PAGE LOG:', msg.text()));
  page.on('pageerror', error => console.log('PAGE ERROR:', error.message));
  page.on('requestfailed', request => console.log('REQUEST FAILED:', request.url(), request.failure().errorText));

  await page.goto('https://thesis-monotoring-system-production-5522.up.railway.app/register', { waitUntil: 'networkidle2' });
  
  console.log('Page loaded. Clicking button...');
  await page.evaluate(() => {
    const btn = document.querySelector('button[type="button"]'); // Try to find the continue button.
    const buttons = document.querySelectorAll('button');
    let continueBtn = null;
    buttons.forEach(b => {
        if (b.innerText.includes('Continue to Questionnaire')) continueBtn = b;
    });
    if (continueBtn) {
        continueBtn.click();
        console.log('Button clicked.');
    } else {
        console.log('Button not found.');
    }
  });

  await new Promise(resolve => setTimeout(resolve, 2000));
  await browser.close();
})();
