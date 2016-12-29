// This computes the dates of Yon Kippur, Passover and Rosh Hashanah
// It does not comput Hanakka
// It uses the 'name' in the inputs, for example
// 'document.passoverdata.yk.value = ykstring;'

function clearData()
{
  document.passoverdata.year.value = "";
  clearResults();
}

function clearResults()
{
  document.passoverdata.p.value = "";
  document.passoverdata.rh.value = "";
  document.passoverdata.yk.value = "";
}

function dayofweek(day, month, year)
{
    // return 0 for sunday, 1 for monday, etc...

  var a = Math.floor((14 - month)/12);
  var y = year - a;
  var m = month + 12*a - 2;
  var d = (day + y + Math.floor(y/4) - Math.floor(y/100) + Math.floor(y/400) + Math.floor(31*m/12)) % 7;
  return d;
}

function weekdayString(dayofweek)
{
  var daynames = new Array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
  if (dayofweek < 0 || dayofweek >= daynames.length)
    return "";
  else
    return daynames[dayofweek];
}

function monthString(monthofyear)
{
  var monthnames = new Array("", "January", "February", "March", "April", "May", "June", 
                             "July", "August", "September", "October", "November", "December");
  if (monthofyear < 1 || monthofyear > monthnames.length)
    return monthnames[0];
  else
    return monthnames[monthofyear];
}

function calculate()
{
  var y = parseInt(document.passoverdata.year.value);
  var g, nf, nrh, nrh2, drh, mrh, mrh2;
  var dyk, nyk, myk;
  var dp, np, mp;


  var rhstring, ykstring, pstring;

    // clear old output
  clearResults();

    // check for validity
  if (y < 1583 || isNaN(y))
  {
    alert("Error! An invalid year was selected.\n" +
          "Please enter a year after 1582.");
    return;
  }

  g = (y % 19) + 1

      nf = Math.floor(y/100) - Math.floor(y/400) - 2;
  nf += 765433/492480*((12*g) % 19);
  nf += (y % 4)/4;
  nf -= (313*y + 89081)/98496;

  nrh = Math.floor(nf);

  mrh = 9;
  mrh2 = mrh;
  nrh2 = nrh;
  if (nrh > 30)
  {
    mrh2 = 10;
    nrh2 = nrh-30;
  }
  drh = dayofweek(nrh2, mrh2, y);

    // postponement rules
    // number 1: postpone by one day if Sunday, Wednesday or Friday
  if (drh == 0 || drh == 3 || drh == 5)
  {
    nrh += 1;
  }
    // number 2:
  else if (drh == 1)
  {
    if ((nf - nrh) >= 23269/25920 && ((12*g) % 19) > 11)
      nrh++;
  }
    // number 3:
  else if (drh == 2)
  {
    if ((nf - nrh) >= 1367/2160 && ((12*g) % 19) > 6)
      nrh += 2;
  }

    // recompute, just in case
  nrh2 = nrh;
  if (nrh > 30)
  {
    mrh = 10;
    nrh2 = nrh-30;
  }
  drh = dayofweek(nrh2, mrh, y);

  rhstring = weekdayString(drh) + " " + monthString(mrh) + " " + nrh2 + ", " + y;
  document.passoverdata.rh.value = rhstring;

    // yom kippur
  nyk = nrh + 9;
  myk = 9;
  if (nyk > 30)
  {
    nyk -= 30;
    myk++;
  }
  dyk = dayofweek(nyk, myk, y);
  ykstring = weekdayString(dyk) + " " + monthString(myk) + " " + nyk + ", " + y;
  document.passoverdata.yk.value = ykstring;

    // passover
  mp = 3;
  np = 21 + nrh;
  if (np > 31)
  {
    np -= 31;
    mp++;
  }
  dp = dayofweek(np, mp, y);
  pstring = weekdayString(dp) + " " + monthString(mp) + " " + np + ", " + y;
  document.passoverdata.p.value = pstring;

}

function isLeap(year)
{
  var a = year % 4;
  var b = year % 100;
  var c = year % 400;

  if (c == 0)
  {
    return true;
  }
  else if (c != 0 && b == 0)
  {
    return false;
  }
  else
  {
    return (a == 0);
  }
}

