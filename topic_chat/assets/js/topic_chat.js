$(document).ready(function() {
    // จัดการการคลิกปุ่มสถานะ
    $('.topic-status button').on('click', function() {
        $('.topic-status button').removeClass('active');
        $(this).addClass('active');
        const section = $(this).data('section');
        $('.topic-section').removeClass('active');
        $(`.topic-section[data-section="${section}"]`).addClass('active');
    });

    // ฟังก์ชันรีเฟรชข้อความ
    function refreshMessages($container, type) {
        const searchTerm = $('#search-input').val();
        const limit = $container.find('.message').length || 5;
        $.ajax({
            url: 'search_topic.php',
            method: 'POST',
            data: {
                type: type,
                offset: 0,
                limit: limit,
                receiver_id: receiverId,
                search: searchTerm
            },
            success: function(response) {
                $container.empty().append(response);
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error);
            }
        });
    }

    // ฟังก์ชันเริ่ม polling
    let pollingInterval;
    let pollingTimeout;
    function startPolling() {
        if (pollingInterval) clearInterval(pollingInterval);
        pollingInterval = setInterval(function() {
            const $beforeContainer = $('.message-container[data-type="before"]');
            const $afterContainer = $('.message-container[data-type="after"]');
            refreshMessages($beforeContainer, 'before');
            refreshMessages($afterContainer, 'after');
        }, 3000); // ตรวจสอบทุก 3 วินาที
    }

    // ฟังก์ชันหยุด polling
    function stopPolling() {
        if (pollingInterval) clearInterval(pollingInterval);
        // หากหยุดนานเกิน 15 วินาที เริ่ม polling ใหม่
        clearTimeout(pollingTimeout);
        pollingTimeout = setTimeout(startPolling, 15000);
    }

    // เริ่ม polling ครั้งแรก
    startPolling();

    // จัดการเมนูดรอปดาวน์
    $(document).on('click', '.menu-button', function() {
        stopPolling(); // หยุด polling เมื่อเปิดเมนู
        const $menuContainer = $(this).closest('.menu-container');
        const $dropdownMenu = $menuContainer.find('.dropdown-menu');
        $dropdownMenu.toggleClass('active');
        $('.dropdown-menu.active').not($dropdownMenu).removeClass('active');
    });

    $(document).on('click', function(event) {
        if (!$(event.target).closest('.menu-container').length) {
            $('.dropdown-menu.active').removeClass('active');
            startPolling(); // เริ่ม polling ใหม่เมื่อปิดเมนู
        }
    });

    // จัดการการร้องขอลบ
    $(document).on('click', '.delete-button', function() {
        const title = $(this).data('title');
        const $container = $(this).closest('.message-container');
        const type = $container.data('type');

        if (confirm('Are you sure you want to request deletion of this topic?')) {
            $.ajax({
                url: 'delete_message.php',
                method: 'POST',
                data: { title: title, receiver_id: receiverId, action: 'request' },
                success: function(response) {
                    if (response === 'success') {
                        refreshMessages($container, type);
                    } else {
                        alert('Failed to send delete request.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error: ", status, error);
                    alert('An error occurred while sending delete request.');
                }
            });
        }
    });

    // จัดการปุ่ม Approve
    $(document).on('click', '.approve-button', function() {
        const title = $(this).data('title');
        const $container = $(this).closest('.message-container');
        const type = $container.data('type');

        $.ajax({
            url: 'delete_message.php',
            method: 'POST',
            data: { title: title, receiver_id: receiverId, action: 'approve' },
            success: function(response) {
                if (response === 'success') {
                    refreshMessages($container, type);
                } else {
                    alert('Failed to approve deletion.');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error);
                alert('An error occurred while approving deletion.');
            }
        });
    });

    // จัดการปุ่ม Reject
    $(document).on('click', '.reject-button', function() {
        const title = $(this).data('title');
        const $container = $(this).closest('.message-container');
        const type = $container.data('type');

        $.ajax({
            url: 'delete_message.php',
            method: 'POST',
            data: { title: title, receiver_id: receiverId, action: 'reject' },
            success: function(response) {
                if (response === 'success') {
                    refreshMessages($container, type);
                } else {
                    alert('Failed to reject deletion.');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error);
                alert('An error occurred while rejecting deletion.');
            }
        });
    });

    // จัดการ live search
    let searchTimeout;
    $('#search-input').on('input', function() {
        clearTimeout(searchTimeout);
        const searchTerm = $(this).val();
        const activeSection = $('.topic-status button.active').data('section');
        const $container = $(`.message-container[data-type="${activeSection}"]`);

        searchTimeout = setTimeout(function() {
            $.ajax({
                url: 'search_topic.php',
                method: 'POST',
                data: { 
                    search: searchTerm,
                    receiver_id: receiverId,
                    type: activeSection,
                    offset: 0,
                    limit: 5
                },
                success: function(response) {
                    $container.empty().append(response);
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error: ", status, error);
                }
            });
        }, 300);
    });

    // จัดการปุ่ม View More
    $(document).on('click', '.view-more', function() {
        const $button = $(this);
        const type = $button.data('type');
        const count = parseInt($button.data('count'));
        const total = parseInt($button.data('total'));
        const searchTerm = $button.data('search') || $('#search-input').val();

        $.ajax({
            url: 'search_topic.php',
            method: 'POST',
            data: { 
                type: type, 
                offset: count, 
                limit: 5, 
                receiver_id: receiverId,
                search: searchTerm
            },
            success: function(response) {
                $button.before(response);
                const newCount = count + 5;
                $button.data('count', newCount);
                if (newCount >= total) {
                    $button.remove();
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error);
            }
        });
    });
});