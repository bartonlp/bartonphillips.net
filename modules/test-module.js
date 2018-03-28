// BLP 2018-03-18 -- Test module

console.log("in test-module.js");

import x from "../modules/xregexp.js";
console.log("default:", x);

let xx = x.exec("This is a test", /is/);
console.log("test-module, xx:", xx);

let t1 = 1;
let t2 = 2;

export {
  t1,
  t2,
  xx as xyz
};
