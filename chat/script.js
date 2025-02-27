document.addEventListener('DOMContentLoaded', () => {
    const sendForm = document.querySelector('.form-send'); // เลือกเฉพาะฟอร์มส่งข้อความ
    const messageInput = document.querySelector('.input-message');
    const chatBox = document.querySelector('.message-container');

    // ส่งข้อความเมื่อกดปุ่มส่ง
    if (sendForm) { // ตรวจสอบว่าเจอฟอร์มหรือไม่
        sendForm.addEventListener('submit', (e) => {
            e.preventDefault(); // ป้องกันการรีเฟรชหน้าเฉพาะฟอร์มนี้

            const message = messageInput.value;
            const currentScrollPosition = chatBox.scrollTop;

            if (message.trim() !== '') {
                fetch('index.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `message=${encodeURIComponent(message)}`
                })
                .then(response => response.text())
                .then(() => {
                    messageInput.value = '';
                    loadMessages(currentScrollPosition);
                })
                .catch(error => console.error('Error:', error));
            }
        });
    }

    // ฟังก์ชันโหลดข้อความ
    function loadMessages(scrollPosition = null) {
        fetch('load_messages.php')
            .then(response => response.text())
            .then(data => {
                chatBox.innerHTML = data;
                if (scrollPosition !== null) {
                    chatBox.scrollTop = scrollPosition;
                } else {
                    chatBox.scrollTop = chatBox.scrollHeight;
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // โหลดข้อความทุก 1 วินาที
    setInterval(() => {
        const currentScroll = chatBox.scrollTop;
        loadMessages(currentScroll);
    }, 1000);

    // โหลดข้อความครั้งแรก
    loadMessages();
});

// Function to handle scrolling and showing/hiding the button
function handleScroll() {
    const chatBox = document.querySelector('.chat-box');
    const scrollButton = document.querySelector('.scroll-to-bottom');
    const threshold = 50;

    if (chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight <= threshold) {
        scrollButton.style.display = 'none';
    } else {
        scrollButton.style.display = 'block';
    }
}

// Add event listeners
const chatBox = document.querySelector('.chat-box');
chatBox.addEventListener('scroll', handleScroll);

document.querySelector('.scroll-to-bottom').addEventListener('click', () => {
    chatBox.scrollTop = chatBox.scrollHeight;
    handleScroll();
});

// เรียก handleScroll และเลื่อนไปล่างสุดเมื่อโหลดหน้า
window.onload = () => {
    handleScroll();
    chatBox.scrollTop = chatBox.scrollHeight;
};

// ระบบแสดง file เมื่อใส่ file
document.addEventListener('DOMContentLoaded', function () {
    const fileInput = document.getElementById('file-input');
    const messageInput = document.querySelector('.input-message');
    const fileNameDisplay = document.querySelector('.file-name-display');
    const wrapFileUpload = document.querySelector('.wrap-file-upload');

    fileInput.addEventListener('change', function () {
        if (fileInput.files.length > 0) {
            const fileName = fileInput.files[0].name;
            fileNameDisplay.innerHTML = `<i class='bx bxs-file-blank'></i> ${fileName}`; // ใช้ innerHTML เพื่อแสดงไอคอน
            wrapFileUpload.classList.remove('hidden'); // แสดง wrap-file-upload
        } else {
            fileNameDisplay.textContent = ''; // ล้างข้อความ
            wrapFileUpload.classList.add('hidden'); // ซ่อน wrap-file-upload
        }
    });

    // เลื่อนลงไปที่ข้อความล่าสุด
    const messageContainer = document.querySelector('.message-container');
    messageContainer.scrollTop = messageContainer.scrollHeight;

    // ปุ่มเลื่อนลง
    const scrollButton = document.querySelector('.scroll-to-bottom');
    scrollButton.addEventListener('click', function () {
        messageContainer.scrollTop = messageContainer.scrollHeight;
    });
});