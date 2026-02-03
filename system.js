// HELIX SYSTEM CORE v3.2 [PATH FIX + AUDIO BY CHAPTER/FRACTION]

// Визначення правильного шляху до папки assets
const isSubFolder = window.location.pathname.includes('/chapter2/') || window.location.pathname.includes('/chapter1/');
const pathPrefix = isSubFolder ? '../' : '';

// Глава з URL (chapter1/ vs chapter2/) або з глобальної змінної
function getCurrentChapter() {
    if (window.HELIX_CHAPTER) return window.HELIX_CHAPTER;
    if (window.location.pathname.includes('/chapter1/')) return 'ch1';
    if (window.location.pathname.includes('/chapter2/')) return 'ch2';
    return 'ch2';
}

// BGM по главі та фракції (конфіг з audio_config.json або fallback)
const BGM_BY_CHAPTER = { ch1: 'ambience.mp3', ch2: 'ambience.mp3' };
const BGM_BY_FACTION = { OLYMPOS: 'ambience_olympos.mp3', ORIGIN: 'ambience_origin.mp3', THEMIS: 'ambience_themis.mp3', MOIRAI: '' };
const BGM_FALLBACK = 'ambience.mp3';
let audioConfig = null; // { chapters: {}, factions: {}, default_volume: 0.3 } — з audio_config.json
let currentBGMFile = null;
let currentBGMAudio = null;

fetch(pathPrefix + 'audio_config.json').then(function(r) { return r.ok ? r.json() : Promise.reject(); }).then(function(c) {
    audioConfig = c && (c.chapters || c.factions) ? c : null;
}).catch(function() { audioConfig = null; });

function getBGMUrl() {
    var bg = audioConfig && audioConfig.background_tracks && Array.isArray(audioConfig.background_tracks) && audioConfig.background_tracks.length > 0;
    if (bg) {
        var list = audioConfig.background_tracks;
        var file = list[Math.floor(Math.random() * list.length)];
        if (file) return pathPrefix + 'assets/audio/' + file;
    }
    const faction = window.HELIX_BGM_FACTION;
    if (faction) {
        const factionFile = (audioConfig && audioConfig.factions && audioConfig.factions[faction]) || BGM_BY_FACTION[faction];
        if (factionFile && String(factionFile).trim()) {
            return pathPrefix + 'assets/audio/' + factionFile;
        }
    }
    const chapter = getCurrentChapter();
    const file = (audioConfig && audioConfig.chapters && audioConfig.chapters[chapter]) || BGM_BY_CHAPTER[chapter] || BGM_FALLBACK;
    return pathPrefix + 'assets/audio/' + file;
}

function getBGMAudio() {
    const url = getBGMUrl();
    if (currentBGMFile === url && currentBGMAudio) return currentBGMAudio;
    currentBGMFile = url;
    if (currentBGMAudio) {
        currentBGMAudio.pause();
        currentBGMAudio = null;
    }
    currentBGMAudio = new Audio(url);
    currentBGMAudio.loop = true;
    currentBGMAudio.volume = (audioConfig && typeof audioConfig.default_volume === 'number') ? audioConfig.default_volume : 0.3;
    return currentBGMAudio;
}

// SFX та BGM
const SFX = {
    click: new Audio(pathPrefix + 'assets/audio/click.mp3'),
    hover: new Audio(pathPrefix + 'assets/audio/hover.mp3'),
    error: new Audio(pathPrefix + 'assets/audio/error.mp3'),
    glitch: new Audio(pathPrefix + 'assets/audio/glitch.mp3')
};
Object.defineProperty(SFX, 'bgm', { get: getBGMAudio });

SFX.hover.volume = 0.2;
SFX.click.volume = 0.5;

function stopAllSounds() {
    if (currentBGMAudio) { currentBGMAudio.pause(); currentBGMAudio.currentTime = 0; }
    SFX.glitch.pause(); SFX.error.pause();
}

function playSound(name) {
    const audioAllowed = localStorage.getItem('helix_audio_enabled') === 'true';
    if (!audioAllowed) return;

    try {
        if (name === 'bgm') {
            const bgm = getBGMAudio();
            bgm.currentTime = 0;
            const promise = bgm.play();
            if (promise !== undefined) promise.catch(function() {});
            return;
        }
        if (SFX[name]) {
            SFX[name].currentTime = 0;
            const promise = SFX[name].play();
            if (promise !== undefined) promise.catch(function() {});
        }
    } catch (e) { console.warn("Audio missing or error:", name); }
}

// Перемикання BGM по фракції (викликати з профілю після завантаження юзера)
window.setBGMByFaction = function(faction) {
    window.HELIX_BGM_FACTION = faction || null;
    if (localStorage.getItem('helix_audio_enabled') === 'true' && currentBGMAudio) {
        const wasPlaying = !currentBGMAudio.paused;
        currentBGMFile = null;
        currentBGMAudio = null;
        if (wasPlaying) playSound('bgm');
    }
};

window.setBGMByChapter = function(chapter) {
    window.HELIX_CHAPTER = chapter || null;
    if (localStorage.getItem('helix_audio_enabled') === 'true' && currentBGMAudio) {
        const wasPlaying = !currentBGMAudio.paused;
        currentBGMFile = null;
        currentBGMAudio = null;
        if (wasPlaying) playSound('bgm');
    }
};

// --- INITIALIZATION ---
document.addEventListener('DOMContentLoaded', () => {
    // Check if there is a start screen overlay visible
    const startScreen = document.getElementById('start-screen');
    const isStartVisible = startScreen && getComputedStyle(startScreen).display !== 'none';

    // If no start screen (e.g. personnel page), check memory and play bgm
    if (!isStartVisible) {
        if (localStorage.getItem('helix_audio_enabled') === 'true') {
            playSound('bgm');
        }
    }

    attachInterfaceSounds();
});

// GLOBAL FUNCTIONS
window.initAudioContext = function() {
    localStorage.setItem('helix_audio_enabled', 'true');
    playSound('bgm');
    playSound('click');
    attachInterfaceSounds();
};

window.disableAudioContext = function() {
    localStorage.setItem('helix_audio_enabled', 'false');
    stopAllSounds();
};

window.triggerErrorSound = function() { playSound('error'); };

function attachInterfaceSounds() {
    const els = document.querySelectorAll('a, button, .action-btn, .person-card, .btn, .btn-test, .option-btn, .close-btn');
    els.forEach(el => {
        // Remove old listeners to prevent duplicates
        el.removeEventListener('mouseenter', hoverHandler);
        el.removeEventListener('click', clickHandler);
        // Add new
        el.addEventListener('mouseenter', hoverHandler);
        el.addEventListener('click', clickHandler);
    });
}

const hoverHandler = () => playSound('hover');
const clickHandler = () => playSound('click');
