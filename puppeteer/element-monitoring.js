'use strict';

const puppeteer = require('puppeteer');
const languages = ['fr','nl','en'];

(async() => {
  const browser = await puppeteer.launch();
  const page = await browser.newPage();

  for (let lang of languages) {
    await page.goto('https://example.com/' + lang);
    const selector = '#my-id';
    if (await page.$(selector) !== null){
      console.log('Element found ' + lang);
    } else {
      console.log('Element not found ' + lang);
    }
  }

  await browser.close();
})();
