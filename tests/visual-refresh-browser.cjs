// Run against tests/e2e-server.php after seeding the isolated E2E database.
const {chromium}=require(process.env.QR_PLAYWRIGHT || 'playwright');
const assert=require('node:assert/strict'),fs=require('node:fs');
const base='http://127.0.0.1:8766',out='tmp/e2e/redesign';
fs.mkdirSync(out,{recursive:true});
(async()=>{
 const browser=await chromium.launch({channel:'msedge',headless:true});
 const errors=[],failures=[],checks=[];
 try{
 const context=await browser.newContext({viewport:{width:1440,height:1050},colorScheme:'light'});
 await context.route('**/*',r=>{const u=new URL(r.request().url());return u.hostname==='127.0.0.1'||u.protocol==='data:'?r.continue():r.abort()});
 const p=await context.newPage();p.setDefaultTimeout(12000);p.on('pageerror',e=>errors.push(e.message));p.on('response',r=>{if(r.status()>=500)failures.push(r.url())});
 async function visit(path,name,width=1440){await p.setViewportSize({width,height:1000});const r=await p.goto(base+path);assert(r.status()<400,`${name}: HTTP ${r.status()}`);await p.waitForTimeout(180);const overflow=await p.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1);assert(!overflow,`${name} has page overflow at ${width}px`);await p.screenshot({path:`${out}/${name}-${width}.png`,fullPage:true});checks.push(`${name} ${width}px`);console.log(`PASS ${name} ${width}px`)}
 async function login(who){await p.goto(base+'/login');await p.locator('[name=email]').fill(who+'@e2e.example.com');await p.locator('[name=password]').fill('ZemTab-E2E-Only-2026!');await Promise.all([p.waitForNavigation(),p.getByRole('button',{name:'Sign in',exact:true}).click()]);assert(new URL(p.url()).pathname.startsWith('/restaurant/'),'Login '+who+' failed: '+await p.textContent('body'))}
 await visit('/','landing');await visit('/','landing',375);await p.locator('#mobile-menu-toggle').click();assert(await p.locator('#mobile-menu').isVisible());await p.locator('#mobile-menu a[href="#features"]').click();assert(!(await p.locator('#mobile-menu').isVisible()));checks.push('Landing mobile navigation');
 await visit('/demo','demo',375);await visit('/login','login');await p.getByRole('button',{name:'Show password'}).click();assert.equal(await p.locator('[name=password]').getAttribute('type'),'text');await visit('/login','login',375);
 await login('nazra');
 const pages=[['/restaurant/menu-items','menu-editor'],['/restaurant/categories','categories'],['/restaurant/settings','settings'],['/restaurant/settings?information_board=1','information-editor'],['/restaurant/tables','qr-studio']];
 for(const [url,name] of pages)await visit(url,name);
 for(const [url,name] of pages){await visit(url,name,375);await p.getByRole('button',{name:'Menu',exact:true}).click();await p.locator('#dashboard-navigation').waitFor({state:'visible'});assert(await p.locator('#dashboard-navigation a[aria-current=page]').count()===1,`${name} active navigation`)}
 await p.setViewportSize({width:1440,height:1050});await p.goto(base+'/restaurant/menu-items');await p.getByRole('button',{name:'Switch color theme'}).click();assert(await p.locator('html').evaluate(e=>e.classList.contains('dark')));await p.screenshot({path:out+'/menu-editor-dark.png',fullPage:true});checks.push('Dashboard dark theme');
 await p.getByRole('button',{name:'Switch color theme'}).click();
 await visit('/r/e2e-nazra/menu','guest-menu',375);await visit('/r/e2e-nazra/menu?information=1','guest-guide',375);await visit('/r/e2e-full/table/204','guest-ordering',375);
 await p.setViewportSize({width:1440,height:1000});await p.goto(base+'/restaurant/menu-items');await p.locator('form[action$="/logout"] button').click();await login('full');
 for(const [url,name] of [['/restaurant/dashboard','overview'],['/restaurant/orders','workboard'],['/restaurant/analytics','analytics'],['/restaurant/cashier-reports','cashier-reports'],['/restaurant/staff-profiles','staff-profiles'],['/restaurant/service-requests','service-requests'],['/restaurant/delivery','delivery']]){await visit(url,name);await visit(url,name,375)}
 await p.goto(base+'/restaurant/delivery');const pickup=p.locator('select[x-ref=pickupItem]');await pickup.selectOption({index:1});await p.getByRole('button',{name:'Add',exact:true}).click();await p.locator('input[name="items[0][quantity]"]').waitFor({state:'attached'});assert.equal(await p.locator('input[name="items[0][quantity]"]').inputValue(),'1');assert.equal(await p.locator('input[name="items[0][id]"]').inputValue(),await pickup.locator('option').nth(1).getAttribute('value'));await p.getByRole('button',{name:'Remove',exact:true}).click();assert.equal(await p.locator('input[name="items[0][id]"]').count(),0);checks.push('Pickup item selection and removal');
 await p.setViewportSize({width:1440,height:1000});await p.goto(base+'/restaurant/dashboard');await p.locator('#dashboard-locale').selectOption('ar');await p.waitForTimeout(300);assert.equal(await p.locator('html').getAttribute('dir'),'rtl');assert(!(await p.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1)));await p.screenshot({path:out+'/dashboard-rtl.png',fullPage:true});checks.push('RTL desktop dashboard');await visit('/restaurant/dashboard','dashboard-rtl-mobile',375);assert.equal(await p.locator('html').getAttribute('dir'),'rtl');checks.push('RTL mobile dashboard');
 await p.locator('#dashboard-locale').selectOption('en');await p.waitForTimeout(200);await p.setViewportSize({width:1440,height:1000});await p.locator('form[action$="/logout"] button').click();await p.goto(base+'/login');await p.locator('[name=email]').fill('admin@e2e.example.com');await p.locator('[name=password]').fill('ZemTab-E2E-Only-2026!');await Promise.all([p.waitForURL('**/admin/**'),p.getByRole('button',{name:'Sign in',exact:true}).click()]);
 for(const [url,name] of [['/admin/dashboard','admin-overview'],['/admin/restaurants','admin-accounts'],['/admin/payments','admin-payments'],['/admin/database','admin-database'],['/admin/users','admin-users'],['/admin/demo-requests','admin-demo-requests'],['/admin/subscriptions','admin-subscriptions'],['/admin/payment-settings','admin-payment-settings']]){await visit(url,name);await visit(url,name,375)}

 await visit('/r/e2e-photos/table/204','photo-menu',375);
 await p.setViewportSize({width:1440,height:1000});await p.goto(base+'/admin/dashboard');await p.locator('form[action$="/logout"] button').click();await login('profiled');await visit('/restaurant/profile-select','profile-picker');await visit('/restaurant/profile-select','profile-picker',375);
 await p.getByRole('button',{name:/Hotel Manager/}).click();const profileForm=p.locator('form[action$="/profile-login"]');await profileForm.locator('[name=password]').fill('ZemTab-E2E-Only-2026!');await Promise.all([p.waitForURL('**/restaurant/dashboard'),profileForm.getByRole('button',{name:'Enter',exact:true}).click()]);checks.push('Profile password login');
 await visit('/','landing',320);await visit('/login','login',320);await visit('/r/e2e-photos/table/204','photo-menu',320);
 assert.deepEqual(errors,[],'JavaScript errors');assert.deepEqual(failures,[],'Server errors');fs.writeFileSync(out+'/report.json',JSON.stringify({checks,errors,failures},null,2));console.log(`PASS ${checks.length} visual refresh browser checks; no overflow, JavaScript errors or server errors.`);
 }finally{await browser.close()}
})().catch(e=>{console.error(e);process.exitCode=1});
