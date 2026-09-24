const jsdom = require("jsdom");
const { JSDOM } = jsdom;
const https = require('https');

https.get('https://thesis-monotoring-system-production-5522.up.railway.app/register', (res) => {
    let data = '';
    res.on('data', chunk => data += chunk);
    res.on('end', () => {
        const dom = new JSDOM(data, { runScripts: "dangerously", resources: "usable" });
        dom.window.console.log = (...args) => console.log('DOM LOG:', ...args);
        dom.window.console.error = (...args) => console.log('DOM ERROR:', ...args);
        
        setTimeout(() => {
            console.log('Timeout reached. Errors should be above.');
            
            // Try to trigger the button click
            const btn = dom.window.document.querySelector('button[type="button"]');
            if (btn) {
                console.log('Found button, clicking...');
                try {
                    btn.click();
                } catch(e) {
                    console.log('Error clicking:', e.message);
                }
            }
        }, 3000);
    });
});
