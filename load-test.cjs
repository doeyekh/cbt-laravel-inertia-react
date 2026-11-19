const http = require('http');
const { URL } = require('url');

// Configuration
const BASE_URL = 'http://localhost:8080';
const NUM_CONCURRENT_REQUESTS = 40;
const EXAM_SCHEDULE_ID = '1'; // Adjust as needed

// Autossave payload - sending answers for questions
const payload = {
    answers: {
        "1": "option-1",
        "2": "option-2",
        "3": "option-3",
        "4": "option-4",
        "5": "option-5"
    }
};

console.log(`Starting load test: ${NUM_CONCURRENT_REQUESTS} concurrent autosave requests`);
console.log(`Target: PATCH ${BASE_URL}/siswa/exams/${EXAM_SCHEDULE_ID}/answers`);

const startTime = Date.now();

// Create concurrent requests
const promises = [];

for (let i = 0; i < NUM_CONCURRENT_REQUESTS; i++) {
    promises.push(new Promise((resolve, reject) => {
        const url = new URL(`/siswa/exams/${EXAM_SCHEDULE_ID}/answers`, BASE_URL);
        url.searchParams.set('', ''); // Add any needed query params
        
        const options = {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                // In a real test, you'd include auth tokens here
                // 'Authorization': 'Bearer <token>'
            }
        };

        const req = http.request(url, options, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => {
                resolve({
                    statusCode: res.statusCode,
                    statusMessage: res.statusMessage,
                    data: data.substring(0, 200) // Limit output
                });
            });
        });

        req.on('error', reject);
        req.write(JSON.stringify(payload));
        req.end();
    }));
}

// Wait for all requests to complete
Promise.allSettled(promises)
    .then(results => {
        const endTime = Date.now();
        const duration = (endTime - startTime) / 1000;
        
        console.log(`\nLoad test completed in ${duration.toFixed(2)} seconds`);
        
        const statusCodes = results.map(r => {
            if (r.status === 'fulfilled') {
                return r.value.statusCode;
            }
            return 'error';
        });
        
        const statusCounts = {};
        statusCodes.forEach(code => {
            statusCounts[code] = (statusCounts[code] || 0) + 1;
        });
        
        console.log('Status code distribution:');
        console.log(JSON.stringify(statusCounts, null, 2));
        
        console.log(`\nSuccessful requests: ${Object.keys(statusCounts).filter(k => k >= 200 && k < 300).reduce((a, b) => a + statusCounts[k], 0)}`);
        console.log(`Failed requests: ${Object.keys(statusCounts).filter(k => k >= 400).reduce((a, b) => a + statusCounts[k], 0)}`);
        
        process.exit(0);
    })
    .catch(err => {
        console.error('Load test error:', err);
        process.exit(1);
    });
