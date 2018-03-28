// app.js
// This is the http2 server for stuff in bartonphillipsnet/

const fs = require('fs'),
https = require('spdy'),
path = require('path'),
mime = require('mime'),
express = require('express');

var port = normalizePort(process.env.PORT || '7000');

var httpsAddress = port;
const app = express();
app.set('port', port);

var httpsOptions = {
  // /etc/letsencrypt/live and /etc/letsencrypt/archive need to have
  // /group=barton and chmod g+rw.
  // They are normally group=root and group has no permissions.
  key: fs.readFileSync("/etc/letsencrypt/live/bartonphillips.net/privkey.pem"),
  cert:  fs.readFileSync("/etc/letsencrypt/live/bartonphillips.net/fullchain.pem")
};

const server = https.createServer(httpsOptions, app).listen(httpsAddress);

server.on('error', onError);
server.on('listening', onListening);

/**
 * Event listener for HTTP server "error" event.
 */

function onError(error) {
  if(error.syscall !== 'listen') {
    console.log("not listen: ", error.syscall);
    throw error;
  }

  var bind = typeof port === 'string'
             ? 'Pipe ' + port
             : 'Port ' + port;

  // handle specific listen errors with friendly messages
  switch (error.code) {
    case 'EACCES':
      console.error(bind + ' requires elevated privileges');
      //process.exit(1);
      break;
    case 'EADDRINUSE':
      console.error(bind + ' is already in use');
      //process.exit(1);
      break;
    default:
      console.error("sitch default", error);
      throw error;
  }
};

/**
 * Event listener for HTTP server "listening" event.
 */

function onListening() {
  console.log(`Listening on: ${port}`);
};

function normalizePort(val) {
  var port = parseInt(val, 10);

  if (isNaN(port)) {
    // named pipe
    return val;
  }

  if (port >= 0) {
    // port number
    return port;
  }

  return false;
};

// NOW DO THE EXPRESS STUFF

// Catch all

app.use(function(req, res, next) {
  if(req.hostname == 'bartonphillips.net') {
    next();
  } else {
    console.log("headers.host: ", req.headers.host);
    console.log("Hostname: ", req.hostname);
    console.log("Org Url: %s, Url: %s", req.originalUrl, req.url);
    console.log("ERROR: Return");
    next(new Error('Bad Route'));
  }
});

app.get("*", function(req, res, next) {
  if(req.url == '/favicon.ico') next();
  console.log("FILE:", req.url);

  if((req.url.indexOf('node_modules') !== -1) && (req.url.indexOf('.js') === -1)) {
    req.url += ".js";

    let ext = path.extname(req.url);
    let type = mime.getType(ext);
    console.log("ext:", ext, " type:", type);
  
    fs.readFile("/var/www/bartonphillipsnet"+req.url, 'utf8', (err, data) => {
      if(err) next(err);

      data = data.replace(/module\.exports\s+=/g, "export default ");
    
      res.set("Access-Control-Allow-Origin", "*");
      res.type(type);
    
      res.send(data);
      res.end();
      //next();
    });
  } else {
    res.sendFile("/var/www/bartonphillipsnet"+req.url,
                 {headers: {"Access-Control-Allow-Origin":"*"}}, (err) => {
      if(err) {
        next(err);
      }
      res.end();
      //next();
    });
  }
});

// catch 404 and forward to error handler

app.use(function(req, res, next) {
  console.log("req:", req.url);
  var err = new Error('Not Found');
  err.status = 404;
  next(err);
});

// development error handler
// will print stacktrace

// To remove development mode set the 'env' to blank. Uncomment to
// disable development.
//app.set('env', '');
/*
if(app.get('env') === 'development') {
  // Error middle ware has 4 args! Must have 'next' even if not used.
  app.use(function(err, req, res, next) {
    console.log("REQ: ", req.url);

    res.status(err.status || 500);
    if(err.status != 404) {
      req.url = null;
    }

    res.render('error', {
      message: err.message,
      url: req.url,
      status: err.status,
      error: err
    }); 
  });
}
*/
// production error handler
// no stacktraces leaked to user

app.use(function(err, req, res, next) {
  res.status(err.status || 500);
  if(err.status != 404) {
    req.url = null;
  }
  console.log("MSG: %s", err.message);
  console.log("URL: %s", req.url);
  console.log("ERROR: ", err);
/*
  res.render('error', {
    message: err.message,
    url: req.url,
    status: err.status,
    error: {}
  });
*/  
});

module.exports = app;
