    const socket = io("http://localhost:3000", {
        transports: ["websocket", "polling"]
    });
    const card = document.getElementById('card');
    const userIdTxt = document.getElementById('user-id');
    const horaTxt = document.getElementById('hora');

    // El navegador bloquea sonidos si no haces clic primero
    document.body.onclick = () => console.log("Audio activado");
    const audio = new Audio("https://www.myinstants.com/es/instant/cuak-94975/?utm_source=copy&utm_medium=share");
    socket.on('nueva-checada', (data) => {
        // 1. Mostrar datos
        userIdTxt.innerText = "ID: " + data.id;
        horaTxt.innerText = data.hora;

        // 2. Animación y Sonido
        card.classList.add('success');
        audio.play();
        // 3. Limpiar después de 3 segundos
        setTimeout(() => {
            card.classList.remove('success');
        }, 3000);
    });