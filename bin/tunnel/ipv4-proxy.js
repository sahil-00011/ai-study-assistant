const http = require('http');
const net = require('net');

const server = http.createServer((req, res) => {
  res.writeHead(400);
  res.end('CONNECT proxy only');
});

server.on('connect', (req, clientSocket, head) => {
  const parts = req.url.split(':');
  const host = parts[0];
  const port = parseInt(parts[1], 10) || 443;

  const serverSocket = net.connect({
    host: host,
    port: port,
    family: 4 // Strict IPv4 to bypass Indian ISP IPv6 drop
  }, () => {
    clientSocket.write('HTTP/1.1 200 Connection Established\r\n\r\n');
    if (head && head.length > 0) {
      serverSocket.write(head);
    }
    serverSocket.pipe(clientSocket);
    clientSocket.pipe(serverSocket);
  });

  serverSocket.on('error', () => {
    clientSocket.destroy();
  });
  clientSocket.on('error', () => {
    serverSocket.destroy();
  });
});

server.listen(8888, '127.0.0.1', () => {
  console.log('IPv4 proxy active on 127.0.0.1:8888');
});
