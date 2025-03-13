self.onmessage = function (event) {
    if (event.data === 'start') {
        console.log('Banchmark started');
        const memoryHog = [];
        let allocatedMB = 0;

        function allocateChunk() {
            try {
                memoryHog.push(new ArrayBuffer(1024 * 1024 * 100)); // 100 МБ
                allocatedMB += 100;
                console.log(`Allocated ${allocatedMB} MB`);
                setTimeout(allocateChunk, 1); // Добавляем следующий chunk через 100 мс
            } catch (error) {
                console.error('Memory allocation failed:', error);
            }
        }

        allocateChunk();
    }
};