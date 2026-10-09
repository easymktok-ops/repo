"""Servidor SMTP falso para pruebas: acepta AUTH LOGIN y guarda el mensaje recibido. Uso: python3 fake-smtp.py PUERTO ARCHIVO"""
import socketserver, sys, base64

port, out = int(sys.argv[1]), sys.argv[2]

class H(socketserver.StreamRequestHandler):
    def send(self, s): self.wfile.write((s + "\r\n").encode())
    def handle(self):
        self.send("220 fake ESMTP")
        log, rcpts = [], []
        while True:
            line = self.rfile.readline().decode().rstrip("\r\n")
            if not line: return
            u = line.upper()
            if u.startswith("EHLO"): self.send("250-fake"); self.send("250 AUTH LOGIN")
            elif u == "AUTH LOGIN": self.send("334 VXNlcm5hbWU6"); user = base64.b64decode(self.rfile.readline()).decode(); self.send("334 UGFzc3dvcmQ6"); pw = base64.b64decode(self.rfile.readline()).decode(); log.append(f"AUTH {user}:{pw}"); self.send("235 ok")
            elif u.startswith("MAIL FROM"): log.append(line); self.send("250 ok")
            elif u.startswith("RCPT TO"): rcpts.append(line); log.append(line); self.send("250 ok")
            elif u == "DATA":
                self.send("354 go")
                data = []
                while True:
                    l = self.rfile.readline().decode()
                    if l.rstrip("\r\n") == ".": break
                    data.append(l)
                open(out, "w").write("\n".join(log) + "\n---\n" + "".join(data))
                self.send("250 queued")
            elif u == "QUIT": self.send("221 bye"); return
            else: self.send("250 ok")

socketserver.TCPServer.allow_reuse_address = True
socketserver.TCPServer(("127.0.0.1", port), H).serve_forever()
