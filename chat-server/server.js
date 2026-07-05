// Serveur temps réel pour le chat Konekt.
// Démarrer avec :  cd chat-server && npm install && node server.js
const http = require('http');
const { Server } = require('socket.io');

const server = http.createServer((req, res) => {
  res.writeHead(200, { 'Content-Type': 'text/plain' });
  res.end('Konekt chat server running');
});

const io = new Server(server, { cors: { origin: '*' } });
const users = new Map(); // userId -> socketId

io.on('connection', (socket) => {
  socket.on('register', (userId) => {
    users.set(String(userId), socket.id);
    console.log(`User ${userId} connecté (${socket.id})`);
  });

  socket.on('message', (msg) => {
    const target = users.get(String(msg.receiver_id));
    if (target) io.to(target).emit('message', msg);
  });

  socket.on('disconnect', () => {
    for (const [uid, sid] of users) if (sid === socket.id) users.delete(uid);
  });
});

const PORT = 3001;
server.listen(PORT, () => console.log(`Socket.io prêt sur http://localhost:${PORT}`));
