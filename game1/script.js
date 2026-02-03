const logger = document.getElementById('typewriter');

function typeMessage(text) {
    logger.innerHTML = '';
    let i = 0;
    const interval = setInterval(() => {
        logger.innerHTML += text.charAt(i);
        i++;
        if (i >= text.length) clearInterval(interval);
    }, 30);
}

function selectLab(type) {
    const messages = {
        'LOGOS': '> Доступ до Архівів Мнемозіни надано. Архітекторе, не вірте очам.',
        'VESSEL': '> Зразки Нарвік-А активовані. Ваша кров тепер належить проекту.',
        'HORKOS': '> Протокол "Потоп" активовано. Аудиторе, готуйте вироки.'
    };

    typeMessage(messages[type]);

    // Візуальний фідбек
    document.querySelectorAll('.sector-card').forEach(c => c.style.opacity = '0.2');
    event.currentTarget.style.opacity = '1';
    event.currentTarget.style.borderColor = '#00ff00';

    setTimeout(() => {
        // Тут ми можемо перейти до сторінки 2-го кроку (обладнання)
        // window.location.href = `lab_config.html?sector=${type}`;
        alert(`СИСТЕМА: Сектор ${type} закріплено за вами.`);
    }, 2000);
}

// Початкове привітання
window.onload = () => typeMessage("> Оберіть сектор для розгортання протоколу ПАНДОРА...");
