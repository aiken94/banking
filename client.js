const ws = new WebSocket('ws://staging.gochargemenow.com:8080');

ws.onopen = () => {
    console.log('Connected to server');
    
    // Create data as a JavaScript object
    const data = {
        "action": "StartTransaction",
        "page": 2,
        "filter": "active",
        "sort": "asc"
    };
    
    // Convert the object to a JSON string before sending
    ws.send(JSON.stringify(data));
};

ws.onmessage = (event) => {
    let serverHolder = document.getElementById('server');
    let responseHolder = document.getElementById('response');

    // Display the server used
    serverHolder.innerHTML  =  ws.url;

    // Display the response from the server
    responseHolder.innerHTML = event.data;
    
    console.log('Message from server:', event.data);
};

