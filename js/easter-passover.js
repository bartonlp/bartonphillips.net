function movepic(img_name,img_src){
  document[img_name].src=img_src;
}

function gmod(m,n){

// generalized modulo function (m mod n) - also valid for negative values of m

  return ((m%n)+n)%n;
}

function addspace(x){
  if(x > 9) return x;
  if(x < 10) return " "+x;
}

function yeartype(year,cal){

// determine year type (yt=0 for common; yt=1 for bissextile) for Julian calendar (cal='j') or Gregorian calendar (cal='g')

  yt=0;
  if(gmod(year,4) == 0) yt=1;
  if(cal == 'g'){
    if(gmod(year,100) == 0) yt=0;
    if(gmod(year,400) == 0) yt=1;    
  }
  return yt;
}

var domletname=new Array("G","F","E","D","C","B","A","AG","GF","FE","ED","DC","CB","BA");
var epactname=new Array("*(XXX)","I","II","III","IV","V","VI","VII","VIII","IX","X","XI","XII","XIII",
                        "XIV","XV","XVI","XVII","XVIII","XIX","XX","XXI","XXII","XXIII","XXIV","XXV",
                        "XXVI","XXVII","XXVIII","XXIX");
var martyrj = new Array("a","b","c","d","e","f","g","h","i","k","l","m","n","p","q","r","s","t","u");
var martyrg=new Array("a","b","c","d","e","f","g","h","i","k","l","m","n","p","q","r","s","t","u",
                      "A","B","C","D","E","F","G","H","M","N","P");
var monthname=new Array("January","February","March","April","May","June","July","August","September",
                        "October","November","December");
var wdname=new Array("Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday");
var yearlength=new Array("common","bissextile");

function setyear(){

  var today=new Date();

  document.eastercalc.year.value=today.getFullYear();
  easter_passover();
}

function setyear1926(){

  var today=new Date();

  document.easter1926.year.value=today.getFullYear();
  easter1926();
}

function seteasteryear(){

  var today=new Date();

  document.easteryear.year.value=today.getFullYear();
  easteryear();
}

function addoneyear(){
  document.eastercalc.year.value=year+1;
  easter_passover();
}

function subtractoneyear(){
  document.eastercalc.year.value=year-1;
  easter_passover();
}

function addoneyear1926(){
  document.easter1926.year.value=year+1;
  easter1926();
}

function subtractoneyear1926(){
  document.easter1926.year.value=year-1;
  easter1926();
}

function addoneeasteryear(){
  document.easteryear.year.value=year+1;
  easteryear();  
}

function subtractoneeasteryear(){
  document.easteryear.year.value=year-1;
  easteryear();  
}

function days2day(days,yeartype)
{
  if(days < 1) {
    m=Math.floor((days+yeartype-3)/30.6);
    return Math.floor(days+yeartype-3-30.6*m)+1;
  }
  if(days > 0) {
    m=Math.floor((days+30)/30.6)+2;
    return Math.floor(days+183-30.6*(m+3))+1;
  }
}

function days2month(days,yeartype){
  if(days < 1){
    return Math.floor((days+yeartype-3)/30.6)+3;
  }
  if(days > 0){
    return Math.floor((days+30)/30.6)+2;
  }
}

function jul_easter(year,emod){

// Julian Easter Sunday date

  a=gmod(year,19);
  gn=a+1;                                        // Golden Number
  b=gmod(year,4);
  c=gmod(year,7);
  d=gmod((19*a+15),30);
  if((emod == 1) && (gn == 1)) d=d+1;
  e=gmod((2*b+4*c-d+6),7);
  fmj=113+d;                                     // Easter full moon [days after -92 March]
  dmj=fmj+e+1;                                   // Easter Sunday [days after -92 March] 
  fmmj=Math.floor(fmj/31);                       // month Easter full moon [March = 3; April = 4]
  fmdj=gmod(fmj,31)+1;                           // day Easter full moon
  esmj=Math.floor(dmj/31);                       // month Easter Sunday [March = 3; April = 4]
  esdj=gmod(dmj,31)+1;                           // day Easter Sunday
  sc=gmod((year+8),28)+1;                        // Number in Solar Cycle
  s=gmod((4+Math.floor(5*year/4)),7);
  dl=domletname[s+7*yeartype(year,'j')];
  return new Array(dmj,esdj,esmj,fmj,fmdj,fmmj,gn,sc,dl);   
}

function greg_easter(year){

// Gregorian Easter Sunday date

  a=gmod(year,19);
  gn=a+1;                                           // Golden Number
  b=Math.floor(year/100);
  c=gmod(year,100);
  d=Math.floor(b/4);
  e=gmod(b,4);
  f=Math.floor((b+8)/25);
  g=Math.floor((b-f+1)/3);
  h=gmod((19*a+b-d-g+15),30);
  i=Math.floor(c/4);
  k=gmod(c,4);
  l=gmod((2*e+2*i-h-k+4),7);
  m=Math.floor((a+11*h+22*l)/451);
  ep=gmod((11*a+8-b+d+Math.floor((8*b+13)/25)),30);
  if(ep <= 23) fmg=136-ep;                          // Easter full moon [days after -92 March]
  if((ep == 24) || (ep == 25)) fmg=141;
  if((ep == 25) && (gn > 11)) fmg=140;
  if(ep >= 26) fmg=166-ep;
  dmg=114+h-7*m+l;                                  // Easter Sunday [days after -92 March]
  fmmg=Math.floor(fmg/31);                          // month Easter full moon [March = 3; April = 4]
  fmdg=gmod(fmg,31)+1;                              // day Easter full moon
  esmg=Math.floor(dmg/31);                          // month Easter Sunday [March = 3; April = 4]
  esdg=gmod(dmg,31)+1;                              // day Easter Sunday
  s=0;
  if((b == 0) && (gmod(year,400) /= 0)) s=5;
  sc=gmod((year+8),28)+1;                           // Number in Solar Cycle
  s=gmod((6+Math.floor(5*year/4)-Math.floor(year/100)+Math.floor(year/400)),7);
  dl=domletname[s+7*yeartype(year,'g')];
  return new Array(dmg,esdg,esmg,fmg,fmdg,fmmg,gn,ep,sc,dl);
}

function easter_passover(){

  year=parseFloat(document.eastercalc.year.value);
  
  prol=document.eastercalc.proleptic.selectedIndex;
  emod=document.eastercalc.julianmode.selectedIndex;

  var j_easter=Array;
  j_easter=jul_easter(year,emod);

  var g_easter=Array;
  g_easter=greg_easter(year);

  gn=j_easter[6];
  ep=g_easter[7];

  document.eastercalc.domletj.value=j_easter[8];
  document.eastercalc.domletg.value=g_easter[9];
  if((prol == 0) && (year < 1582)) document.eastercalc.domletg.value='--';
  document.eastercalc.numaur.value=gn;
  document.eastercalc.litmartyrj.value=martyrj[gn-1];
  document.eastercalc.epact.value=epactname[ep];
  if((ep == 25) && (gn > 11)) document.eastercalc.epact.value='25';
  if((prol == 0) && (year < 1582)) document.eastercalc.epact.value='--';
  if(!isNaN(ep)) document.eastercalc.litmartyrg.value=martyrg[ep-1];
  if(ep == 0) document.eastercalc.litmartyrg.value=martyrg[29];
  if((prol == 0) && (year < 1582)) document.eastercalc.litmartyrg.value='-';

// Dionysian Easter Sunday (Julian calendar)

  document.eastercalc.fmdayj.value=addspace(j_easter[4]);
  document.eastercalc.fmmonj.value=monthname[j_easter[5]-1];
  document.eastercalc.dayj.value=addspace(j_easter[1]);
  document.eastercalc.monj.value=monthname[j_easter[2]-1];

// Convert Dionysian Easter Sunday date to the Gregorian calendar

  diff_jg=Math.floor(year/100)-Math.floor(year/400)-2;  // offset between Julian and Gregorian calendar [after 0 March]
  
  fmjg=j_easter[3]+diff_jg;
  fmmjg=Math.floor((fmjg-62)/30.6);
  fmdjg=Math.floor(fmjg-62-30.6*fmmjg)+1;
  fmmjg=fmmjg+2;

  dmjg=j_easter[0]+diff_jg;
  mjg=Math.floor((dmjg-62)/30.6);
  djg=Math.floor(dmjg-62-30.6*mjg)+1;
  mjg=mjg+2;

  document.eastercalc.fmdayjg.value=addspace(fmdjg);
  document.eastercalc.fmmonjg.value=monthname[fmmjg-1];
  if((prol == 0) && (year <= 1582)){
    document.eastercalc.fmdayjg.value='--';
    document.eastercalc.fmmonjg.value=' --- ';
  }
  document.eastercalc.dayjg.value=addspace(djg);
  document.eastercalc.monjg.value=monthname[mjg-1];
  if((prol == 0) && (year <= 1582)){
    document.eastercalc.dayjg.value='--';
    document.eastercalc.monjg.value=' --- ';
  }
  document.eastercalc.delc.value=diff_jg;
  if((prol == 0) && (year < 1582)) document.eastercalc.delc.value='--';

// Gregorian Easter Sunday date (Gregorian calendar)

  document.eastercalc.fmdayg.value=addspace(g_easter[4]);
  document.eastercalc.fmmong.value=monthname[g_easter[5]-1];
  if((prol == 0) && (year <= 1582)){
    document.eastercalc.fmdayg.value='--';
    document.eastercalc.fmmong.value=' --- ';
  }
  document.eastercalc.dayg.value=addspace(g_easter[1]);
  document.eastercalc.mong.value=monthname[g_easter[2]-1];
  if((prol == 0) && (year <= 1582)){
    document.eastercalc.dayg.value='--';
    document.eastercalc.mong.value=' --- ';
  }
  
// Days between Dionysian and Gregorian Easter Sunday

  ddjg=dmjg-g_easter[0];

  document.eastercalc.deljg.value=ddjg;
  if((prol == 0) && (year <= 1582)) document.eastercalc.deljg.value='--';

// 15 Nisan in the Jewish calendar

  a=gmod((12*year+12),19);
  b=gmod(year,4);
  s=(5*(1979335-313*year)+(765433*a))/492480+b/4;
  q=Math.floor(s);
  r=s-q;
  c=gmod((q+3*year+5*b+1),7);
  dmh=q+diff_jg+92;
  p=0;
  if((c == 2) || (c == 4) || (c == 6)) p=1;           // because of Adu
  if((c == 1) && (a > 6) && (r > 1366/2160)) p=2;     // because of Gatarad
  if((c == 0) && (a > 11) && (r > 23268/25920)) p=1;  // because of Batu Thakpad
  dmh=dmh+p;
  ddhj=dmjg-dmh;
  ddhg=g_easter[0]-dmh;
  if(year <= 1582) dmh=dmh-dcjg;
  mh=Math.floor((dmh-62)/30.6);
  dh=Math.floor(dmh-62-30.6*mh)+1;
  mh=mh+2;
  wh=gmod(c+p+5,7); 
  am=year+3760;

  document.eastercalc.amundi.value=am;
  document.eastercalc.dayh.value=addspace(dh);
  document.eastercalc.monh.value=monthname[mh-1];
  document.eastercalc.delhj.value=ddhj;
  document.eastercalc.delhg.value=ddhg;
  if((prol == 0) && (year <= 1582)) document.eastercalc.delhg.value='--';

 document.eastercalc.weekd.value=wdname[wh];
}

function easter1926(){

  year=parseFloat(document.easter1926.year.value);
  
  var g_easter=Array;
  g_easter=greg_easter(year);

// 1926 Proposal of the League of Nations

  day1926=15-gmod((Math.floor(5*year/4)-Math.floor(year/100)+Math.floor(year/400)+6),7); 
  dd1926g=123+day1926-g_easter[0];

  document.easter1926.year.value=year;
  document.easter1926.day1926.value=addspace(day1926);
  document.easter1926.del1926g.value=dd1926g;
  if(year < 1926){
    document.easter1926.day1926.value='--';
    document.easter1926.del1926g.value=' -- ';
  }
}

function easteryear(){

  year=parseFloat(document.easteryear.year.value);
 
  var g_easter=Array;
  g_easter=greg_easter(year);

  gn=g_easter[6];
  ep=g_easter[7];
  
  document.easteryear.numaur.value=gn;
  document.easteryear.epact.value=epactname[ep];
  if((ep == 25) && (gn > 11)) document.easteryear.epact.value='25';

// determine year type (yt=0 for common; yt=1 for bissextile)

  yt=yeartype(year,'g');

  document.easteryear.ytype.value=yearlength[yt];

  document.easteryear.dominical.value=g_easter[9];

  des=g_easter[1];
  mes=g_easter[2];
  dl=g_easter[9];

  document.easteryear.dluna14.value=addspace(g_easter[4]);
  document.easteryear.mluna14.value=monthname[g_easter[5]-1];
  document.easteryear.lunage.value=14+(g_easter[0]-g_easter[3]);
  document.easteryear.dayg.value=addspace(des);
  document.easteryear.mong.value=monthname[mes-1];

  dmar0=31*(mes-3)+des;        // days since 0 March

  if((dl == "A") || (dl == "AG")) dep=-58-yt;
  if((dl == "B") || (dl == "BA")) dep=-57-yt;
  if((dl == "C") || (dl == "CB")) dep=-56-yt;
  if((dl == "D") || (dl == "DC")) dep=-55-yt;
  if((dl == "E") || (dl == "ED")) dep=-54-yt;
  if((dl == "F") || (dl == "FE")) dep=-53-yt;
  if((dl == "G") || (dl == "GF")) dep=-52-yt;
  
  if((dl == "A") || (dl == "BA")) dadv=278;
  if((dl == "B") || (dl == "CB")) dadv=272;
  if((dl == "C") || (dl == "DC")) dadv=273;
  if((dl == "D") || (dl == "ED")) dadv=274;
  if((dl == "E") || (dl == "FE")) dadv=275;
  if((dl == "F") || (dl == "GF")) dadv=276;
  if((dl == "G") || (dl == "AG")) dadv=277;

  
  dd=dep;                    // Feast Holy Name of Jesus (Epiphany)
  document.epiphanyseason.weekep0.value=(dd-dmar0)/7;
  document.epiphanyseason.dholyname.value=addspace(days2day(dd,yt));
  document.epiphanyseason.mholyname.value=monthname[days2month(dd,yt)-1];

  dd=dep+7;                      // 1st Sunday after Epiphany
  document.epiphanyseason.weekep1.value=(dd-dmar0)/7;
  document.epiphanyseason.depiphsun1.value=addspace(days2day(dd,yt));
  document.epiphanyseason.mepiphsun1.value=monthname[days2month(dd,yt)-1];

  dd=dep+14;                    // 2nd Sunday after Epiphany
  document.epiphanyseason.weekep2.value=(dd-dmar0)/7;
  document.epiphanyseason.depiphsun2.value=addspace(days2day(dd,yt));
  document.epiphanyseason.mepiphsun2.value=monthname[days2month(dd,yt)-1];
  if(dd >= (dmar0-63)){
    document.epiphanyseason.weekep2.value='--';
    document.epiphanyseason.depiphsun2.value='--';
    document.epiphanyseason.mepiphsun2.value='   ---   ';
  }
  
  dd=dep+21;                   // 3rd Sunday after Epiphany
  document.epiphanyseason.weekep3.value=(dd-dmar0)/7;
  document.epiphanyseason.depiphsun3.value=addspace(days2day(dd,yt));
  document.epiphanyseason.mepiphsun3.value=monthname[days2month(dd,yt)-1];
  if(dd >= (dmar0-63)){
    document.epiphanyseason.weekep3.value='--';
    document.epiphanyseason.depiphsun3.value='--';
    document.epiphanyseason.mepiphsun3.value='   ---   ';
  }
  
  dd=dep+28;                   // 4th Sunday after Epiphany
  document.epiphanyseason.weekep4.value=(dd-dmar0)/7;
  document.epiphanyseason.depiphsun4.value=addspace(days2day(dd,yt));
  document.epiphanyseason.mepiphsun4.value=monthname[days2month(dd,yt)-1];
  if(dd >= (dmar0-63)){
    document.epiphanyseason.weekep4.value='--';
    document.epiphanyseason.depiphsun4.value='--';
    document.epiphanyseason.mepiphsun4.value='   ---   ';
  }
  
  dd=dep+35;                   // 5th Sunday after Epiphany
  document.epiphanyseason.weekep5.value=(dd-dmar0)/7;
  document.epiphanyseason.depiphsun5.value=addspace(days2day(dd,yt));
  document.epiphanyseason.mepiphsun5.value=monthname[days2month(dd,yt)-1];
  if(dd >= (dmar0-63)){
    document.epiphanyseason.weekep5.value='--';
    document.epiphanyseason.depiphsun5.value='--';
    document.epiphanyseason.mepiphsun5.value='   ---   ';
  }
  
  dd=dep+42;                   // 6th Sunday after Epiphany
  document.epiphanyseason.weekep6.value=(dd-dmar0)/7;
  document.epiphanyseason.depiphsun6.value=addspace(days2day(dd,yt));
  document.epiphanyseason.mepiphsun6.value=monthname[days2month(dd,yt)-1];
  if(dd >= (dmar0-63)){
    document.epiphanyseason.weekep6.value='--';
    document.epiphanyseason.depiphsun6.value='--';
    document.epiphanyseason.mepiphsun6.value='   ---   ';
  }
  
  dd=dmar0-63;                 // Septuagesima Sunday
  document.easterseason.dseptuasun.value=addspace(days2day(dd,yt));
  document.easterseason.mseptuasun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0-56;                 // Sexagesima Sunday
  document.easterseason.dsexasun.value=addspace(days2day(dd,yt));
  document.easterseason.msexasun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0-49;                 // Shrove Sunday
  document.easterseason.dshrovesun.value=addspace(days2day(dd,yt));
  document.easterseason.mshrovesun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0-46;                 // Ash Wednesday
  document.easterseason.dashwednes.value=addspace(days2day(dd,yt));
  document.easterseason.mashwednes.value=monthname[days2month(dd,yt)-1];

  dd=dmar0-42;                 // First Sunday in Lent
  document.easterseason.dlent1sun.value=addspace(days2day(dd,yt));
  document.easterseason.mlent1sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0-35;                 // Midlent Sunday
  document.easterseason.dlent2sun.value=addspace(days2day(dd,yt));
  document.easterseason.mlent2sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0-28;                 // 3rd Sunday in Lent
  document.easterseason.dlent3sun.value=addspace(days2day(dd,yt));
  document.easterseason.mlent3sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0-21;                 // 4th Sunday in Lent
  document.easterseason.dlent4sun.value=addspace(days2day(dd,yt));
  document.easterseason.mlent4sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0-14;                 // Passion Sunday
  document.easterseason.dlent5sun.value=addspace(days2day(dd,yt));
  document.easterseason.mlent5sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0-7;                  // Palm Sunday
  document.easterseason.dlent6sun.value=addspace(days2day(dd,yt));
  document.easterseason.mlent6sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0-3;                  // Holy Thursday
  document.easterseason.dholythur.value=addspace(days2day(dd,yt));
  document.easterseason.mholythur.value=monthname[days2month(dd,yt)-1];

  dd=dmar0-2;                  // Good Friday
  document.easterseason.dgoodfri.value=addspace(days2day(dd,yt));
  document.easterseason.mgoodfri.value=monthname[days2month(dd,yt)-1];

  dd=dmar0-1;                  // Holy Saturday
  document.easterseason.dholysat.value=addspace(days2day(dd,yt));
  document.easterseason.mholysat.value=monthname[days2month(dd,yt)-1];

  dd=dmar0;                    // Easter Sunday
  document.easterseason.deastersun.value=addspace(days2day(dd,yt));
  document.easterseason.meastersun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+7;                  // 1st Sunday after Easter
  document.easterseason.deaster1sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter1sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+14;                 // 2nd Sunday after Easter
  document.easterseason.deaster2sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter2sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+21;                 // 3rd Sunday after Easter
  document.easterseason.deaster3sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter3sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+28;                 // 4th Sunday after Easter
  document.easterseason.deaster4sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter4sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+35;                 // Rogation Sunday
  document.easterseason.deaster5sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter5sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+39;                 // Ascension Day
  document.easterseason.dascension.value=addspace(days2day(dd,yt));
  document.easterseason.mascension.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+42;                 // 6th Sunday after Easter
  document.easterseason.deaster6sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter6sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+49;                 // Pentecost
  document.easterseason.deaster7sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter7sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+56;                 // Trinity Sunday
  document.easterseason.deaster8sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter8sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+60;                 // Corpus Christi (outside of the USA)
  document.easterseason.dcorpuschristi.value=addspace(days2day(dd,yt));
  document.easterseason.mcorpuschristi.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+63;                 // Corpus Christi (USA)
  document.easterseason.deaster9sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter9sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+70;                 // 10th Sunday after Easter
  document.easterseason.deaster10sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter10sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+77;                 // 11th Sunday after Easter
  document.easterseason.deaster11sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter11sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+84;                 // 12th Sunday after Easter
  document.easterseason.deaster12sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter12sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+91;                 // 13th Sunday after Easter
  document.easterseason.deaster13sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter13sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+98;                 // 14th Sunday after Easter
  document.easterseason.deaster14sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter14sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+105;                // 15th Sunday after Easter
  document.easterseason.deaster15sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter15sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+112;                // 16th Sunday after Easter
  document.easterseason.deaster16sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter16sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+119;                // 17th Sunday after Easter
  document.easterseason.deaster17sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter17sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+126;                // 18th Sunday after Easter
  document.easterseason.deaster18sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter18sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+133;                // 19th Sunday after Easter
  document.easterseason.deaster19sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter19sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+140;                // 20th Sunday after Easter
  document.easterseason.deaster20sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter20sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+147;                // 21st Sunday after Easter
  document.easterseason.deaster21sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter21sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+154;                // 22nd Sunday after Easter
  document.easterseason.deaster22sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter22sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+161;                // 23rd Sunday after Easter
  document.easterseason.deaster23sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter23sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+168;                // 24th Sunday after Easter
  document.easterseason.deaster24sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter24sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+175;                // 25th Sunday after Easter
  document.easterseason.deaster25sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter25sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+182;                // 26th Sunday after Easter
  document.easterseason.deaster26sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter26sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+189;                // 27th Sunday after Easter
  document.easterseason.deaster27sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter27sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+196;                // 28th Sunday after Easter
  document.easterseason.deaster28sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter28sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+203;                // 29th Sunday after Easter
  document.easterseason.deaster29sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter29sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+210;                // 30th Sunday after Easter
  document.easterseason.deaster30sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter30sun.value=monthname[days2month(dd,yt)-1];

  dd=dmar0+217;                // 31st Sunday after Easter
  document.easterseason.deaster31sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter31sun.value=monthname[days2month(dd,yt)-1];
  if(dd >= dadv){
    document.easterseason.deaster31sun.value='--';
    document.easterseason.measter31sun.value='   ---   ';
  }
  
  dd=dmar0+224;                // 32nd Sunday after Easter
  document.easterseason.deaster32sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter32sun.value=monthname[days2month(dd,yt)-1];
  if(dd >= dadv){
    document.easterseason.deaster32sun.value='--';
    document.easterseason.measter32sun.value='   ---   ';
  }
  
  dd=dmar0+231;                // 33rd Sunday after Easter
  document.easterseason.deaster33sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter33sun.value=monthname[days2month(dd,yt)-1];
  if(dd >= dadv){
    document.easterseason.deaster33sun.value='--';
    document.easterseason.measter33sun.value='   ---   ';
  }
  
  dd=dmar0+238;                // 34th Sunday after Easter
  document.easterseason.deaster34sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter34sun.value=monthname[days2month(dd,yt)-1];
  if(dd >= dadv){
    document.easterseason.deaster34sun.value='--';
    document.easterseason.measter34sun.value='   ---   ';
  }
  
  dd=dmar0+245;                // 35th Sunday after Easter
  document.easterseason.deaster35sun.value=addspace(days2day(dd,yt));
  document.easterseason.measter35sun.value=monthname[days2month(dd,yt)-1];
  if(dd >= dadv){
    document.easterseason.deaster35sun.value='--';
    document.easterseason.measter35sun.value='   ---   ';
  }
  
  dd=dadv;                     // 1st Advent Sunday
  document.adventseason.weekadv1.value=(dd-dmar0)/7;
  document.adventseason.dadvent1sun.value=addspace(days2day(dd,yt));
  document.adventseason.madvent1sun.value=monthname[days2month(dd,yt)-1];

  dd=dadv+7;                   // 2nd Advent Sunday
  document.adventseason.weekadv2.value=(dd-dmar0)/7;
  document.adventseason.dadvent2sun.value=addspace(days2day(dd,yt));
  document.adventseason.madvent2sun.value=monthname[days2month(dd,yt)-1];

  dd=dadv+14;                  // 3rd Advent Sunday
  document.adventseason.weekadv3.value=(dd-dmar0)/7;
  document.adventseason.dadvent3sun.value=addspace(days2day(dd,yt));
  document.adventseason.madvent3sun.value=monthname[days2month(dd,yt)-1];
  
  dd=dadv+17;                  // Ember Wednesday in Advent
  document.adventseason.demberadvwed.value=addspace(days2day(dd,yt));
  document.adventseason.memberadvwed.value=monthname[days2month(dd,yt)-1];

  dd=dadv+19;                  // Ember Friday in Advent
  document.adventseason.demberadvfri.value=addspace(days2day(dd,yt));
  document.adventseason.memberadvfri.value=monthname[days2month(dd,yt)-1];

  dd=dadv+20;                  // Ember Saturday in Advent
  document.adventseason.demberadvsat.value=addspace(days2day(dd,yt));
  document.adventseason.memberadvsat.value=monthname[days2month(dd,yt)-1];

  dd=dadv+21;                  // 4th Advent Sunday
  document.adventseason.weekadv4.value=(dd-dmar0)/7;
  document.adventseason.dadvent4sun.value=addspace(days2day(dd,yt));
  document.adventseason.madvent4sun.value=monthname[days2month(dd,yt)-1];
  
  dd=dadv+28;                  // 5th Advent Sunday
  document.adventseason.weekadv5.value=(dd-dmar0)/7;
  document.adventseason.dadvent5sun.value=addspace(days2day(dd,yt));
  document.adventseason.madvent5sun.value=monthname[days2month(dd,yt)-1];
}