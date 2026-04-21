export function addVideo(videoContainer, videoId) {
    try {
        fetchVideoDetails(videoId).then((videoData) => {
            if (!videoData) return;

            const placeholderImage = videoContainer.getAttribute("data-youtube-placeholder") ||
                `https://images.weserv.nl/?url=img.youtube.com/vi/${videoId}/sddefault.jpg&output=webp`;

            const startTime = parseInt(videoContainer.getAttribute("data-youtube-start-play-video"), 10) || 0;
            const hoverPlay = videoContainer.getAttribute("data-hover") === "true";
            const controlsEnabled = videoContainer.getAttribute("data-controls") === "true";
            const muteEnabled = videoContainer.getAttribute("data-mute") === "true";

            videoContainer.innerHTML = `
                <div class="youtube-wrapper" style="position: relative; cursor: pointer;">
                    <img src="${placeholderImage}" alt="YouTube Video Thumbnail" class="youtube-thumbnail" style="width: 100%; display: block;">
                    <button class="youtube-play">▶</button>
                </div>
            `;

            const wrapper = videoContainer.querySelector(".youtube-wrapper");
            const playButton = videoContainer.querySelector(".youtube-play");

            if (!wrapper || !playButton) {
                console.error("Error: Missing wrapper or play button.");
                return;
            }

            let player;
            let playerCreated = false;

            const createPlayer = () => {
                if (playerCreated) return;
                playerCreated = true;

                videoContainer.innerHTML = `
                    <div id="player-${videoId}" style="position: relative;">
                        <div class="overlay" style="position:absolute; top:0; left:0; width:100%; height:100%; z-index:10; cursor:pointer;"></div>
                    </div>
                `;

                player = new YT.Player(`player-${videoId}`, {
                    videoId,
                    height: '100%',
                    width: '100%',
                    playerVars: {
                        autoplay: 1,  // Plays video automatically (may require mute)
                        mute: muteEnabled ? 1 : 0,  // Mute required for autoplay in most browsers
                        controls: controlsEnabled ? 1 : 0,  // Show/hide player controls
                        start: startTime,  // Start time in seconds
                        loop: 1,  // Enables looping
                        playlist: videoId,  // Required for looping to work
                        playsinline: 1,  // Ensures inline playback on mobile devices
                        iv_load_policy: controlsEnabled ? 1 : 3,  // Annotation policy
                        enablejsapi: 1,  // Allows JS control
                        origin: window.location.origin  // Domain security measure
                    },
                    events: {
                        onReady: (event) => {
                            const overlay = event.target.getIframe().parentNode.querySelector('.overlay');
                            overlay?.addEventListener('click', () => {
                                event.target.playVideo();
                                overlay.style.display = 'none';
                            });

                            if (!controlsEnabled) {
                                setupHoverControls(videoContainer, event.target);
                            } else {
                                setupScrollControls(event.target);
                            }
                        },
                    },
                });
            };

            const onYouTubeIframeAPIReady = () => {
                playButton.addEventListener("click", (event) => {
                    event.stopPropagation();
                    if (!playerCreated) {
                        createPlayer();
                    } else if (player && typeof player.playVideo === "function") {
                        player.playVideo();
                    } else {
                        console.error("Error: player is not initialized or playVideo is not a function.", player);
                    }
                });

                wrapper.addEventListener("click", () => {
                    if (!playerCreated) {
                        createPlayer();
                    } else if (player && typeof player.playVideo === "function") {
                        player.playVideo();
                    } else {
                        console.error("Error: player is not initialized or playVideo is not a function.", player);
                    }
                });

                if (hoverPlay) {
                    wrapper.addEventListener("mouseenter", () => {
                        if (!playerCreated) {
                            createPlayer();
                        } else if (player && typeof player.playVideo === "function") {
                            player.playVideo();
                        }
                    });

                    wrapper.addEventListener("mouseleave", () => {
                        if (player && typeof player.pauseVideo === "function") {
                            player.pauseVideo();
                        }
                    });
                }
            };


            if (typeof YT === 'undefined' || typeof YT.Player === 'undefined') {
                if (!window.YT_API_Loaded) {
                    window.YT_API_Loaded = true;
                    const tag = document.createElement('script');
                    tag.src = "https://www.youtube.com/iframe_api";
                    document.head.appendChild(tag);
                    window.onYouTubeIframeAPIReady = onYouTubeIframeAPIReady;
                }
            } else {
                onYouTubeIframeAPIReady();
            }
        });
    } catch (error) {
        console.error("Error initializing video container:", error);
    }
}

async function fetchVideoDetails(videoId) {
    try {
        const response = await fetch(`/wp-content/themes/devata/youtube-proxy.php?videoId=${videoId}`);
        if (!response.ok) throw new Error("Failed to fetch video details");
        return await response.json();
    } catch (error) {
        console.error("Error fetching video details:", error);
        return null;
    }
}

function setupHoverControls(wrapper, player) {
    try {
        wrapper.addEventListener("mouseenter", () => player?.playVideo());
        wrapper.addEventListener("mouseleave", () => player?.pauseVideo());
    } catch (error) {
        console.error("Error setting up hover controls:", error);
    }
}

function setupScrollControls(player) {
    try {
        const checkVisibility = () => {
            const playerElement = document.getElementById(`player-${player.getVideoData().video_id}`);
            if (!playerElement) return;

            const rect = playerElement.getBoundingClientRect();
            const fullyVisible = rect.top >= 0 && rect.bottom <= window.innerHeight;

            fullyVisible ? player?.playVideo() : player?.pauseVideo();
        };

        window.addEventListener("scroll", () => requestAnimationFrame(checkVisibility));
    } catch (error) {
        console.error("Error setting up scroll controls:", error);
    }
}