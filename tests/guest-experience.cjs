const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
let saved = null;
const context = vm.createContext({
  document: {addEventListener() {}, hidden:false}, navigator:{onLine:true},
  localStorage:{getItem:()=>saved,setItem:(_,value)=>saved=value},
  setInterval:()=>1,clearInterval(){},setTimeout:()=>1,clearTimeout(){},
  Date,Intl,AbortSignal,console,
});
vm.runInContext(fs.readFileSync('public/assets/guest-experience.js','utf8'), context);
const browser = context.guestBrowser([{category:1,name:'Tiramisu',description:'Espresso cream'},{category:2,name:'Juice',description:'Fresh orange'}]);
assert(browser.hasVisibleItems());
browser.menuSearch = ' ESPRESSO '; assert(browser.categoryHasVisibleItems(1)); assert(!browser.categoryHasVisibleItems(2));
browser.activeCategory='cat-2'; assert(!browser.hasVisibleItems());
browser.resetSearch(); assert(browser.hasVisibleItems());
browser.menuSearch='nothing'; assert(!browser.hasVisibleItems());
const cart = context.menuCart({menuItems:[],cartKey:'test',vatPercentage:15,serviceChargePercentage:10,labels:{}});
cart.add({id:1,name:'Tiramisu',price:750},2);
assert.equal(cart.total(),1500); assert.equal(cart.finalTotal(),1875);
cart.add({id:1,name:'Tiramisu',price:750},99); assert.equal(cart.count(),50);
cart.dec(1); assert.equal(cart.count(),49);
cart.items[0].note='No cocoa'; cart.persistCart();
const restored=context.menuCart({cartKey:'test'}); restored.init(); assert.equal(restored.items[0].note,'No cocoa');
saved=JSON.stringify({items:[{id:1,quantity:51,price:1},{id:2,quantity:0,price:1},{id:3,quantity:2,price:-1},{id:4,quantity:2,price:100}]});
const sanitized=context.menuCart({cartKey:'test'}); sanitized.init(); assert.equal(sanitized.count(),2); assert.equal(sanitized.items[0].id,4);
cart.items=[{id:1,quantity:1,price:750}];cart.dec(1);assert.equal(cart.count(),0);
let prevented=false; cart.syncForm({preventDefault(){prevented=true;}}); assert(prevented);
const progress=context.guestOrderProgress('/status','new',{new:['Sent','Waiting'],preparing:['Preparing','Cooking'],paid:['Paid','Thanks']});
context.fetch=async()=>({ok:true,json:async()=>({status:'preparing'})});
(async()=>{
 await progress.refresh(); assert.equal(progress.status,'preparing'); assert.equal(progress.label(),'Preparing');
 context.navigator.onLine=false; await progress.refresh(); assert(progress.message.includes('paused'));
 context.navigator.onLine=true;
 context.fetch=async()=>({ok:false,status:404});await progress.refresh();assert(progress.stopped);
 progress.status='paid';assert(progress.terminal());
 console.log('Passed guest browsing, quantity limits, persisted notes, totals, offline handling, and live status checks.');
})().catch(error=>{console.error(error);process.exitCode=1;});
