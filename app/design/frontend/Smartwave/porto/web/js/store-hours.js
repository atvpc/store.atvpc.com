require(['jquery'], function( $ ) {
  "use strict";

  var hours = [
    { "day"      : "Sunday",
      "open"     : 0,
      "close"    : 0,
      "shipping" : 0
      },
    { "day"      : "Monday",
      "open"     : 900,
      "close"    : 1700,
      "shipping" : 1630
    },
    { "day"      : "Tuesday",
      "open"     : 900,
      "close"    : 1700,
      "shipping" : 1630
    },
    { "day"      : "Wednesday",
      "open"     : 900,
      "close"    : 1700,
      "shipping" : 1630
    },
    { "day"      : "Thursday",
      "open"     : 900,
      "close"    : 1700,
      "shipping" : 1630
    },
    { "day"      : "Friday",
      "open"     : 900,
      "close"    : 1700,
      "shipping" : 1630
    },
    { "day"      : "Saturday",
      "open"     : 900,
      "close"    : 1500,
      "shipping" : 0
    }
  ];

  var holidays = {
    "0000-07-04" : "The 4th of July",
    "2019-11-28" : "Thanksgiving",
    "2020-11-26" : "Thanksgiving",
    "0000-12-25" : "Christmas"
  };

  /* ******************** DO NOT EDIT BELOW ******************** */
  function dayName(day) {
    var days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
    return days[day];
  }

  function prettyTime(time) {
    time        = String(time);
    var hours   = time.slice(0, -2);
    var minutes = time.slice(-2);
    var ampm    = null;

    if (hours < 12) {
      ampm = "am";

      if (hours === 0) {
        hours = 12;
      }
    }
    else {
      ampm = "pm";

      if (hours > 12) {
        hours = hours - 12;
      }
    }

    return hours + ":" + minutes + ampm;
  }

  function isDST(estDate) {
    var month = estDate.getMonth() + 1;
    var day = estDate.getDate();
    var dow = estDate.getDay();

    //January, february, and december are out.
    if (month < 3 || month > 11) { return false; }

    //April to October are in
    if (month > 3 && month < 11) { return true; }

    var previousSunday = day - dow;

    //In march, we are DST if our previous sunday was on or after the 8th.
    if (month === 3) { return previousSunday >= 8; }

    //In november we must be before the first sunday to be dst.
    //That means the previous sunday must be before the 1st.
    return previousSunday <= 0;
  }

  $(function() {

    var now = new Date();
    var nowUTC = new Date(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate(), now.getUTCHours(), now.getUTCMinutes(), now.getUTCSeconds());

    //console.log("Local Time: " + now.toLocaleString());
    //console.log("UTC:        " + nowUTC.toLocaleString());

    var nowEST = new Date(nowUTC);
    nowEST.setTime( nowUTC.getTime() - 1000*60*60*5 );

    if (isDST(nowEST) === true) {
      nowEST.setTime( nowUTC.getTime() - 1000*60*60*4 );
      //console.log("EDT:        " + nowEST.toLocaleString());
    }
    else {
      //console.log("EST:        " + nowEST.toLocaleString());
    }

    var time = nowEST.toTimeString();
    var time = time.substr(0,2) +""+ time.substr(3,2); // HHMM ( +""+ prevents from being added together)
    var day = nowEST.getDay();
    var holidayExactDate = nowEST.toISOString().slice(0,10); // YYYY-MM-DD
    var holidayEveryYear = "0000-" + nowEST.toISOString().slice(5,10); // 0000-MM-DD

    /* OPEN / CLOSED STATUS */
    var status = "";
    var hover = "";

    if (typeof holidays[holidayExactDate] != "undefined") {
      /* floating day holidays, like easter and thanksgiving */
      status = "closed";
      hover  = "Sorry, we're closed for " + holidays[holidayExactDate];
    }
    else if (typeof holidays[holidayEveryYear] != "undefined") {
      /* holidays on the same day, like christmas */
      status = "closed";
      hover  = "Sorry, we're closed for " + holidays[holidayEveryYear];
    }
    else {
      /* regular days, non-holiday */
      if (hours[day].open === hours[day].close) {
        status = "closed";
        hover = "Sorry, we're closed on " + dayName(day) + "s";
      }
      else if (time < hours[day].open) {
        status = "closed";
        hover = "We will open today at " + prettyTime( hours[day].open );
      }
      else if (time >= hours[day].close) {
        status = "closed";
        hover  = "Sorry, we're closed for the day";
      }
      else if ( (time - hours[day].open) <= 30 ) {
        status = "opening";
        hover = "If you call is not answered, please try back in a few minutes";
      }
      else if (hours[day].close - time - 40 <= 15) {
        status = "closing";
        hover = "Technical assistance and returns may be asked to call back on the next business day";
      }
      else {
        if (hours[day].shipping === 0 || time >= hours[day].shipping) {
          status = "open-shipping-closed";
        }
        else {
          status = "open";
        }

        hover = "Have a question? Call us, we're open!";
      }
    }

    /* HTML OUTPUT */
    var elText = document.querySelector("#open-closed-text");
    var elImg = document.querySelector("#open-closed-img");

    $(elImg).prop("title", hover);
    $(elImg).prop("src", "/pub/media/store-hours/status-" + status + ".png");

    if (status != "closed") {
      /* don't care about opening, closing, etc */
      status = "open";
      var color = $(elText).attr('data-open-color');
      var text  = $(elText).attr('data-open-text');
    }

    $(elText).prop("title", hover);
    $(elText).prop("style", "color: " + $(elText).attr("data-" + status + "-color"));
    $(elText).html( $(elText).attr("data-" + status + "-text") );
  });
});
