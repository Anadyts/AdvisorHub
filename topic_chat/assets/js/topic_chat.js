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
    function refreshMessages($container, type, page = 1, resultsPerPage = 5) {
        const searchTerm = $('#search-input').val();
        $.ajax({
            url: 'search_topic.php',
            method: 'POST',
            data: {
                type: type,
                page: page,
                receiver_id: receiverId,
                search: searchTerm,
                results_per_page: resultsPerPage
            },
            success: function(response) {
                $container.empty().html(response);
            },
            error: function(xhr, status, error) {
                console.error("ข้อผิดพลาด AJAX: ", status, error);
            }
        });
    }

    // จัดการเมนูดรอปดาวน์
    $(document).on('click', '.menu-button', function() {
        const $menuContainer = $(this).closest('.menu-container');
        const $dropdownMenu = $menuContainer.find('.dropdown-menu');
        $dropdownMenu.toggleClass('active');
        $('.dropdown-menu.active').not($dropdownMenu).removeClass('active');
    });

    $(document).on('click', function(event) {
        if (!$(event.target).closest('.menu-container').length) {
            $('.dropdown-menu.active').removeClass('active');
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
                        alert('ไม่สามารถส่งคำขอลบได้');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("ข้อผิดพลาด AJAX: ", status, error);
                    alert('เกิดข้อผิดพลาดขณะส่งคำขอลบ');
                }
            });
        }
    });

    // จัดการปุ่มอนุมัติการลบ
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
                    alert('ไม่สามารถอนุมัติการลบได้');
                }
            },
            error: function(xhr, status, error) {
                console.error("ข้อผิดพลาด AJAX: ", status, error);
                alert('เกิดข้อผิดพลาดขณะอนุมัติการลบ');
            }
        });
    });

    // จัดการปุ่มปฏิเสธการลบ
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
                    alert('ไม่สามารถปฏิเสธการลบได้');
                }
            },
            error: function(xhr, status, error) {
                console.error("ข้อผิดพลาด AJAX: ", status, error);
                alert('เกิดข้อผิดพลาดขณะปฏิเสธการลบ');
            }
        });
    });

    // จัดการการค้นหาแบบเรียลไทม์
    let searchTimeout;
    $('#search-input').on('input', function() {
        clearTimeout(searchTimeout);
        const searchTerm = $(this).val();
        const activeSection = $('.topic-status button.active').data('section');
        const $container = $(`.message-container[data-type="${activeSection}"]`);

        searchTimeout = setTimeout(function() {
            refreshMessages($container, activeSection);
        }, 300);
    });

    // จัดการการคลิก Pagination
    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        const $container = $(this).closest('.message-container');
        const type = $container.data('type');
        const resultsPerPage = $container.find('.results-per-page').val() || 5;

        // อัปเดตคลาส active เฉพาะปุ่มตัวเลขเท่านั้น
        if (!$(this).hasClass('pagination-arrow')) {
            $container.find('.pagination a').not('.pagination-arrow').removeClass('active');
            $(this).addClass('active');
        }

        refreshMessages($container, type, page, resultsPerPage);
    });

    // โหลดหน้าแรก
    const $initialContainers = $('.message-container');
    $initialContainers.each(function() {
        const $container = $(this);
        const type = $container.data('type');
        refreshMessages($container, type);
    });

    // ฟังก์ชันจาก topic_chat.php
    window.changeResultsPerPage = function(perPage, section) {
        const url = section === 'after' ?
            `?page_after=1&results_per_page=${perPage}` :
            `?page_before=1&results_per_page=${perPage}`;
        window.location.href = url;
    };

    // ฟังก์ชันจาก search_topic.php (เปลี่ยนชื่อเพื่อหลีกเลี่ยงการซ้ำ)
    window.updateResultsPerPage = function(perPage, type) {
        const $container = $(`.message-container[data-type="${type}"]`);
        $.ajax({
            url: 'search_topic.php',
            method: 'POST',
            data: {
                type: type,
                page: 1,
                receiver_id: receiverId,
                search: $('#search-input').val(),
                results_per_page: perPage
            },
            success: function(response) {
                $container.empty().html(response);
            }
        });
    };
});