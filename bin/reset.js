import ZKLib from 'node-zklib';

async function probarReinicio() {
    const zk = new ZKLib(
        '192.168.0.240', // IP del reloj
        4370,
        10000,
        4000
    );

    try {
        console.log('Conectando...');
        await zk.createSocket();

        console.log('Conectado.');

        //const hora = await zk.getInfo();
        //console.log('Hora del reloj:', hora);

        console.log('Enviando CMD_RESTART (1004)...');

        const respuesta = await zk.executeCmd(1004, '');

        console.log('Respuesta:', respuesta);
        console.log('Si el reloj soporta el comando, debería reiniciarse en unos segundos.');

    } catch (error) {
        console.error('Error:', error);
    } finally {
        try {
            await zk.disconnect();
        } catch {}
    }
}

probarReinicio();
