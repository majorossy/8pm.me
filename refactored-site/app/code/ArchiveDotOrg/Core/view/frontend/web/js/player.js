/**
 * ArchiveDotOrg Core Module - Audio Player
 *
 * Features:
 * - Play/pause, stop, next, previous
 * - Shuffle mode
 * - Repeat modes (none, one, all)
 * - Volume control with slider
 * - Keyboard shortcuts
 * - Playlist persistence via localStorage
 * - ARIA accessibility support
 */
define([
    'jquery'
], function ($) {
    'use strict';

    return function (config, element) {
        var playlist = config.playlist || [];
        var playerId = config.playerId || 'archivedotorg-player';
        var storageKey = 'archivedotorg_player_' + playerId;

        if (playlist.length === 0) {
            $(element).hide();
            return;
        }

        var currentTrack = 0;
        var audio = null;
        var isShuffleEnabled = false;
        var repeatMode = 'none'; // 'none', 'one', 'all'
        var shuffleOrder = [];
        var shuffleIndex = 0;

        // Load saved state from localStorage
        function loadState() {
            try {
                var saved = localStorage.getItem(storageKey);
                if (saved) {
                    var state = JSON.parse(saved);
                    currentTrack = state.currentTrack || 0;
                    isShuffleEnabled = state.shuffle || false;
                    repeatMode = state.repeatMode || 'none';
                    if (state.volume !== undefined) {
                        audio.volume = state.volume;
                    }
                    if (currentTrack >= playlist.length) {
                        currentTrack = 0;
                    }
                }
            } catch (e) {
                console.warn('Failed to load player state:', e);
            }
        }

        // Save state to localStorage
        function saveState() {
            try {
                var state = {
                    currentTrack: currentTrack,
                    shuffle: isShuffleEnabled,
                    repeatMode: repeatMode,
                    volume: audio ? audio.volume : 1
                };
                localStorage.setItem(storageKey, JSON.stringify(state));
            } catch (e) {
                console.warn('Failed to save player state:', e);
            }
        }

        // Generate shuffle order
        function generateShuffleOrder() {
            shuffleOrder = [];
            for (var i = 0; i < playlist.length; i++) {
                shuffleOrder.push(i);
            }
            // Fisher-Yates shuffle
            for (var j = shuffleOrder.length - 1; j > 0; j--) {
                var k = Math.floor(Math.random() * (j + 1));
                var temp = shuffleOrder[j];
                shuffleOrder[j] = shuffleOrder[k];
                shuffleOrder[k] = temp;
            }
            // Find current track in shuffle order
            shuffleIndex = shuffleOrder.indexOf(currentTrack);
            if (shuffleIndex === -1) {
                shuffleIndex = 0;
            }
        }

        // Initialize audio element
        function initAudio() {
            audio = new Audio();
            audio.preload = 'metadata';

            audio.addEventListener('ended', function () {
                handleTrackEnded();
            });

            audio.addEventListener('timeupdate', function () {
                updateProgress();
            });

            audio.addEventListener('loadedmetadata', function () {
                updateDuration();
            });

            audio.addEventListener('play', function () {
                updatePlayButton(true);
                announceToScreenReader('Playing: ' + getTrackTitle(currentTrack));
            });

            audio.addEventListener('pause', function () {
                updatePlayButton(false);
            });

            audio.addEventListener('volumechange', function () {
                updateVolumeDisplay();
                saveState();
            });

            audio.addEventListener('error', function (e) {
                console.error('Audio error:', e);
                announceToScreenReader('Error loading track. Skipping to next.');
                playNext();
            });
        }

        // Handle track ended based on repeat mode
        function handleTrackEnded() {
            switch (repeatMode) {
                case 'one':
                    audio.currentTime = 0;
                    play();
                    break;
                case 'all':
                    playNext();
                    break;
                case 'none':
                default:
                    if (getNextTrackIndex() !== null) {
                        playNext();
                    } else {
                        stop();
                        announceToScreenReader('Playlist ended');
                    }
                    break;
            }
        }

        // Get next track index (considering shuffle)
        function getNextTrackIndex() {
            if (isShuffleEnabled) {
                var nextShuffleIndex = shuffleIndex + 1;
                if (nextShuffleIndex >= shuffleOrder.length) {
                    if (repeatMode === 'all') {
                        generateShuffleOrder();
                        return shuffleOrder[0];
                    }
                    return null;
                }
                return shuffleOrder[nextShuffleIndex];
            } else {
                var nextIndex = currentTrack + 1;
                if (nextIndex >= playlist.length) {
                    if (repeatMode === 'all') {
                        return 0;
                    }
                    return null;
                }
                return nextIndex;
            }
        }

        // Get previous track index (considering shuffle)
        function getPreviousTrackIndex() {
            if (isShuffleEnabled) {
                var prevShuffleIndex = shuffleIndex - 1;
                if (prevShuffleIndex < 0) {
                    prevShuffleIndex = shuffleOrder.length - 1;
                }
                return shuffleOrder[prevShuffleIndex];
            } else {
                var prevIndex = currentTrack - 1;
                if (prevIndex < 0) {
                    prevIndex = playlist.length - 1;
                }
                return prevIndex;
            }
        }

        // Get track title for display
        function getTrackTitle(index) {
            var track = playlist[index];
            if (track) {
                return track.artist + ' - ' + track.title;
            }
            return '';
        }

        // Load a track
        function loadTrack(index) {
            if (index < 0 || index >= playlist.length) {
                return false;
            }

            currentTrack = index;
            var track = playlist[currentTrack];

            audio.src = track.mp3;
            updatePlaylistDisplay();
            updateNowPlaying();

            // Update shuffle index if shuffle is enabled
            if (isShuffleEnabled) {
                shuffleIndex = shuffleOrder.indexOf(currentTrack);
            }

            saveState();
            return true;
        }

        // Play current track
        function play() {
            if (!audio.src && playlist.length > 0) {
                loadTrack(0);
            }

            audio.play().catch(function (error) {
                console.error('Play failed:', error);
                announceToScreenReader('Failed to play track');
            });
        }

        // Pause playback
        function pause() {
            audio.pause();
        }

        // Toggle play/pause
        function togglePlay() {
            if (audio.paused) {
                play();
            } else {
                pause();
            }
        }

        // Stop playback
        function stop() {
            audio.pause();
            audio.currentTime = 0;
            updatePlayButton(false);
        }

        // Play next track
        function playNext() {
            var nextIndex = getNextTrackIndex();

            if (nextIndex === null) {
                nextIndex = 0; // Wrap around
            }

            if (isShuffleEnabled) {
                shuffleIndex = shuffleOrder.indexOf(nextIndex);
            }

            if (loadTrack(nextIndex)) {
                play();
            }
        }

        // Play previous track
        function playPrevious() {
            // If more than 3 seconds into track, restart it
            if (audio.currentTime > 3) {
                audio.currentTime = 0;
                return;
            }

            var prevIndex = getPreviousTrackIndex();

            if (isShuffleEnabled) {
                shuffleIndex = shuffleOrder.indexOf(prevIndex);
            }

            if (loadTrack(prevIndex)) {
                play();
            }
        }

        // Toggle shuffle mode
        function toggleShuffle() {
            isShuffleEnabled = !isShuffleEnabled;

            if (isShuffleEnabled) {
                generateShuffleOrder();
            }

            updateShuffleButton();
            saveState();
            announceToScreenReader('Shuffle ' + (isShuffleEnabled ? 'enabled' : 'disabled'));
        }

        // Cycle repeat mode
        function cycleRepeatMode() {
            switch (repeatMode) {
                case 'none':
                    repeatMode = 'all';
                    break;
                case 'all':
                    repeatMode = 'one';
                    break;
                case 'one':
                    repeatMode = 'none';
                    break;
            }

            updateRepeatButton();
            saveState();
            announceToScreenReader('Repeat mode: ' + repeatMode);
        }

        // Update play button display
        function updatePlayButton(isPlaying) {
            var $btn = $(element).find('.jp-play');
            $btn.text(isPlaying ? 'Pause' : 'Play');
            $btn.attr('aria-label', isPlaying ? 'Pause' : 'Play');
            $btn.attr('aria-pressed', isPlaying ? 'true' : 'false');
        }

        // Update shuffle button display
        function updateShuffleButton() {
            var $btn = $(element).find('.jp-shuffle');
            $btn.toggleClass('jp-state-shuffle', isShuffleEnabled);
            $btn.attr('aria-pressed', isShuffleEnabled ? 'true' : 'false');
            $btn.attr('aria-label', 'Shuffle ' + (isShuffleEnabled ? 'on' : 'off'));
        }

        // Update repeat button display
        function updateRepeatButton() {
            var $btn = $(element).find('.jp-repeat');
            $btn.removeClass('jp-state-repeat-none jp-state-repeat-all jp-state-repeat-one');
            $btn.addClass('jp-state-repeat-' + repeatMode);
            $btn.attr('aria-label', 'Repeat: ' + repeatMode);

            var text = 'Repeat';
            if (repeatMode === 'one') {
                text = 'Repeat 1';
            } else if (repeatMode === 'all') {
                text = 'Repeat All';
            }
            $btn.text(text);
        }

        // Update progress bar
        function updateProgress() {
            if (audio.duration) {
                var percent = (audio.currentTime / audio.duration) * 100;
                $(element).find('.jp-play-bar').css('width', percent + '%');
                $(element).find('.jp-current-time').text(formatTime(audio.currentTime));

                // Update ARIA values
                var $seekBar = $(element).find('.jp-seek-bar');
                $seekBar.attr('aria-valuenow', Math.round(audio.currentTime));
                $seekBar.attr('aria-valuemax', Math.round(audio.duration));
            }
        }

        // Update duration display
        function updateDuration() {
            $(element).find('.jp-duration').text(formatTime(audio.duration));
        }

        // Update volume display
        function updateVolumeDisplay() {
            var percent = audio.volume * 100;
            $(element).find('.jp-volume-bar-value').css('width', percent + '%');

            var $volumeSlider = $(element).find('.jp-volume-bar');
            $volumeSlider.attr('aria-valuenow', Math.round(percent));

            var $muteBtn = $(element).find('.jp-mute');
            $muteBtn.text(audio.muted ? 'Unmute' : 'Mute');
            $muteBtn.attr('aria-pressed', audio.muted ? 'true' : 'false');
        }

        // Update playlist display
        function updatePlaylistDisplay() {
            var $list = $(element).find('.jp-playlist ul');
            $list.empty();

            playlist.forEach(function (track, index) {
                var $li = $('<li>')
                    .attr('role', 'option')
                    .attr('aria-selected', index === currentTrack ? 'true' : 'false')
                    .attr('tabindex', index === currentTrack ? '0' : '-1')
                    .data('index', index);

                var $trackInfo = $('<span>')
                    .addClass('jp-playlist-item-title')
                    .text(track.artist + ' - ' + track.title);

                var $duration = $('<span>')
                    .addClass('jp-playlist-item-duration')
                    .text(track.duration || '');

                $li.append($trackInfo).append($duration);

                if (index === currentTrack) {
                    $li.addClass('jp-playlist-current');
                }

                $list.append($li);
            });
        }

        // Update now playing display
        function updateNowPlaying() {
            var track = playlist[currentTrack];

            if (track) {
                $(element).find('.jp-current-track').text(track.artist + ' - ' + track.title);
            }
        }

        // Format time in MM:SS
        function formatTime(seconds) {
            if (isNaN(seconds)) {
                return '0:00';
            }

            var mins = Math.floor(seconds / 60);
            var secs = Math.floor(seconds % 60);

            return mins + ':' + (secs < 10 ? '0' : '') + secs;
        }

        // Seek to position
        function seek(event) {
            var $bar = $(element).find('.jp-seek-bar');
            var offset = event.pageX - $bar.offset().left;
            var percent = offset / $bar.width();
            audio.currentTime = percent * audio.duration;
        }

        // Set volume from click on volume bar
        function setVolumeFromClick(event) {
            var $bar = $(element).find('.jp-volume-bar');
            var offset = event.pageX - $bar.offset().left;
            var percent = Math.max(0, Math.min(1, offset / $bar.width()));
            audio.volume = percent;
            audio.muted = false;
        }

        // Set volume
        function setVolume(percent) {
            audio.volume = Math.max(0, Math.min(1, percent));
            audio.muted = false;
        }

        // Toggle mute
        function toggleMute() {
            audio.muted = !audio.muted;
        }

        // Announce to screen readers
        function announceToScreenReader(message) {
            var $announcer = $(element).find('.jp-sr-announcer');
            if ($announcer.length === 0) {
                $announcer = $('<div>')
                    .addClass('jp-sr-announcer')
                    .attr('role', 'status')
                    .attr('aria-live', 'polite')
                    .attr('aria-atomic', 'true')
                    .css({
                        position: 'absolute',
                        width: '1px',
                        height: '1px',
                        overflow: 'hidden',
                        clip: 'rect(0, 0, 0, 0)',
                        whiteSpace: 'nowrap'
                    });
                $(element).append($announcer);
            }
            $announcer.text(message);
        }

        // Handle keyboard shortcuts
        function handleKeyboard(event) {
            // Only handle if player has focus or no other input is focused
            var tagName = event.target.tagName.toLowerCase();
            if (tagName === 'input' || tagName === 'textarea' || tagName === 'select') {
                return;
            }

            var handled = true;

            switch (event.key) {
                case ' ':
                case 'k':
                case 'K':
                    togglePlay();
                    break;
                case 'ArrowRight':
                    if (event.shiftKey) {
                        playNext();
                    } else {
                        audio.currentTime = Math.min(audio.duration, audio.currentTime + 10);
                    }
                    break;
                case 'ArrowLeft':
                    if (event.shiftKey) {
                        playPrevious();
                    } else {
                        audio.currentTime = Math.max(0, audio.currentTime - 10);
                    }
                    break;
                case 'ArrowUp':
                    setVolume(audio.volume + 0.1);
                    break;
                case 'ArrowDown':
                    setVolume(audio.volume - 0.1);
                    break;
                case 'm':
                case 'M':
                    toggleMute();
                    break;
                case 's':
                case 'S':
                    toggleShuffle();
                    break;
                case 'r':
                case 'R':
                    cycleRepeatMode();
                    break;
                case 'n':
                case 'N':
                    playNext();
                    break;
                case 'p':
                case 'P':
                    playPrevious();
                    break;
                case '0':
                case '1':
                case '2':
                case '3':
                case '4':
                case '5':
                case '6':
                case '7':
                case '8':
                case '9':
                    // Jump to percentage of track
                    var percent = parseInt(event.key, 10) / 10;
                    audio.currentTime = audio.duration * percent;
                    break;
                default:
                    handled = false;
            }

            if (handled) {
                event.preventDefault();
            }
        }

        // Initialize
        initAudio();
        loadState();
        generateShuffleOrder();
        updatePlaylistDisplay();
        updateShuffleButton();
        updateRepeatButton();
        updateVolumeDisplay();

        // Bind events
        $(element).find('.jp-play').on('click', togglePlay);
        $(element).find('.jp-stop').on('click', stop);
        $(element).find('.jp-next').on('click', playNext);
        $(element).find('.jp-previous').on('click', playPrevious);
        $(element).find('.jp-shuffle').on('click', toggleShuffle);
        $(element).find('.jp-repeat').on('click', cycleRepeatMode);

        $(element).find('.jp-seek-bar').on('click', seek);
        $(element).find('.jp-volume-bar').on('click', setVolumeFromClick);

        $(element).find('.jp-mute').on('click', toggleMute);

        $(element).find('.jp-playlist').on('click', 'li', function () {
            var index = $(this).data('index');

            if (loadTrack(index)) {
                play();
            }
        });

        // Playlist keyboard navigation
        $(element).find('.jp-playlist').on('keydown', 'li', function (event) {
            var $current = $(this);
            var index = $current.data('index');

            switch (event.key) {
                case 'Enter':
                case ' ':
                    event.preventDefault();
                    if (loadTrack(index)) {
                        play();
                    }
                    break;
                case 'ArrowDown':
                    event.preventDefault();
                    var $next = $current.next('li');
                    if ($next.length) {
                        $current.attr('tabindex', '-1');
                        $next.attr('tabindex', '0').focus();
                    }
                    break;
                case 'ArrowUp':
                    event.preventDefault();
                    var $prev = $current.prev('li');
                    if ($prev.length) {
                        $current.attr('tabindex', '-1');
                        $prev.attr('tabindex', '0').focus();
                    }
                    break;
            }
        });

        // Global keyboard shortcuts when player is visible
        $(document).on('keydown', function (event) {
            if ($(element).is(':visible')) {
                handleKeyboard(event);
            }
        });

        // Auto-load first track
        if (playlist.length > 0) {
            loadTrack(currentTrack);
        }

        // Set ARIA attributes on container
        $(element).attr('role', 'application');
        $(element).attr('aria-label', 'Audio Player');
    };
});
