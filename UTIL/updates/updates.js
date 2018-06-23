const node_ip = '127.0.0.1', node_port = '1337'
, sites = {
	sitename: 'srvtoken'
	// add more sites if you want (sitename: "srvtoken")
}

const bodyParser = require('body-parser')
, express = require('express'), app = express()
, server = require('http').createServer(app)
, io = require('socket.io')(server)
app.use(bodyParser.json())

app.post('/qr/:site', (req, res) => {
	let site = req.params.site, srvtoken
	if (sites.hasOwnProperty(site))
		srvtoken = sites[site]
	else {
		res.status(403).end()
		return
	}
	let data = req.body
	if(typeof data.srvtoken !== 'undefined' || data.srvtoken === srvtoken || typeof data.room !== 'undefined')
		io.to(site+':'+data.room).emit('update', {
			timestamp: data.timestamp, 
			room: data.room.split(':')[1],
			post_id: data.post_id,
			token: data.clitoken
		})
	res.status(200).end()
})

io.on('connection', function (socket) {
	socket.on('subscribe', function(rooms) {
		if (!(rooms instanceof Array))
			rooms = [rooms]
		rooms.forEach(room => socket.join(room))
	})
})

server.listen(node_port, node_ip)