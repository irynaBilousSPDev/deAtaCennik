import 'slick-carousel';
import $ from "jquery";


let isYouTubeAPILoaded = false; // Prevent multiple loads

function loadYouTubeAPI(callback) {
    if (!isYouTubeAPILoaded) {
        let script = document.createElement('script');
        script.src = "https://www.youtube.com/iframe_api";
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);

        window.onYouTubeIframeAPIReady = function () {
            isYouTubeAPILoaded = true;
            if (callback) callback();
        };
    } else {
        if (callback) callback();
    }
}
export async function fetchYouTubeShorts(sliderContainer) {
    if (!sliderContainer) {
        console.error("Error: sliderContainer not found in the DOM.");
        return;
    }

    const youtubePlaylistId = sliderContainer.dataset.youtubePlaylist;
    if (!youtubePlaylistId) {
        console.error("YouTube Playlist ID is missing.");
        return;
    }

    try {
        loadYouTubeAPI(() => console.log("YouTube API Loaded"));

        const proxyUrl = `/wp-content/themes/devata/youtube-proxy.php?id=${youtubePlaylistId}`;
        // console.log("Fetching from:", proxyUrl);

        const response = await fetch(proxyUrl, {
            method: "GET",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/json"
            }
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error(`Proxy Error Response: ${response.status} ${response.statusText}`);
            console.error("Response Body:", errorText);
            throw new Error(`Failed to fetch YouTube playlist videos`);
        }

        const data = await response.json();
        // console.log("Fetched YouTube Playlist Data:", data);

        if (data.error) {
            console.error("YouTube API Error:", data.details);
            return;
        }

        if (!data.items || data.items.length === 0) {
            console.warn("No videos found in the playlist. Ensure the playlist is public and contains videos.");
            return;
        }

        addVideosToSlider(data.items, sliderContainer);
    } catch (error) {
        console.error("Error fetching YouTube playlist videos:", error);
    }
}

function waitForElement(selector, callback) {
    const observer = new MutationObserver((mutations, observer) => {
        document.querySelectorAll(selector).forEach(element => {
            observer.disconnect();
            callback(element);
        });
    });
    observer.observe(document.body, {childList: true, subtree: true});
}

function addVideosToSlider(videos, sliderContainer) {
    sliderContainer.innerHTML = "";
    videos.forEach(video => {
        const videoId = video.snippet.resourceId?.videoId;
        if (!videoId) return;

        const slide = document.createElement("div");
        slide.classList.add("youtube-slide");
        slide.setAttribute("data-video-id", videoId);
        // console.log(videoId);
        slide.innerHTML = `
            <div class="youtube-wrapper">
<!--                <img src="https://img.youtube.com/vi/${videoId}/sddefault.jpg" alt="${video.snippet.title}" class="youtube-thumbnail">-->
              <img src="https://images.weserv.nl/?url=img.youtube.com/vi/${videoId}/sddefault.jpg&output=webp" alt="${video.snippet.title}" class="youtube-thumbnail">
                <button class="youtube-play">▶</button>
            </div>
        `;

        sliderContainer.appendChild(slide);
    });
    initializeSlick(sliderContainer);
    setupVideoPlayback();
}

let isResizing = false;

export function initializeSlick(selector) {
    $(selector).slick({
        slidesToShow: 6,
        slidesToScroll: 1,
        arrows: true,
        prevArrow: '<button class="slick-prev"></button>',
        nextArrow: '<button class="slick-next"></button>',
        dots: false,
        autoplay: false,
        variableWidth: true,
        lazyLoad: "ondemand",
        responsive: [
            {breakpoint: 1366, settings: {slidesToShow: 4}},
            {breakpoint: 1025, settings: {slidesToShow: 3}},
            {breakpoint: 768, settings: {slidesToShow: 2}},
            {breakpoint: 480, settings: {slidesToShow: 1, arrows: false}}
        ]
    }).on('beforeChange', stopAllVideos)
        .on('afterChange', function () {
            if (!isResizing) {
                setTimeout(playCurrentSlideVideo, 500);
            }
        });

    $(".youtube-slider").on('click', '.slick-prev, .slick-next', function () {
        setTimeout(playCurrentSlideVideo, 500);
    });

    // Detect when window resizing starts
    $(window).on("resize", function () {
        isResizing = true;
        clearTimeout(window.resizedFinished);
        window.resizedFinished = setTimeout(function () {
            isResizing = false;
        }, 500);
    });
}

function setupVideoPlayback() {
    document.querySelectorAll(".youtube-play").forEach(button => {
        button.addEventListener("click", function () {
            stopAllVideos();

            const wrapper = this.closest(".youtube-wrapper");
            if (!wrapper) return;

            const videoId = wrapper.closest(".youtube-slide")?.getAttribute("data-video-id");
            if (!videoId) return;

            playVideo(wrapper, videoId, this);

        });
    });

    window.addEventListener("scroll", () => {
        document.querySelectorAll(".youtube-slide").forEach(slide => {
            const rect = slide.getBoundingClientRect();
            if (rect.bottom < 0 || rect.top > window.innerHeight) {
                stopAllVideos();
            }
        });
    });
}

function stopAllVideos() {
    document.querySelectorAll(".youtube-wrapper iframe").forEach(iframe => {
        iframe.contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":[]}', '*');
    });
}

function playCurrentSlideVideo() {
    const currentSlide = $('.slick-current.slick-active');
    if (!currentSlide) return;

    const playButton = currentSlide.find(".youtube-play");

    if (playButton) {
        playButton.click();
    }
}


function playVideo(wrapper, videoId) {
    wrapper.innerHTML = `
        <iframe 
            src="https://www.youtube-nocookie.com/embed/${videoId}?enablejsapi=1&modestbranding=0&rel=0&showinfo=0&autoplay=1&controls=1&mute=1&playsinline=1" 
            frameborder="0" allow="autoplay; encrypted-media" allowfullscreen>
        </iframe>  
    `;
    // Append Pause Button
    const pauseButton = document.createElement("button");
    pauseButton.classList.add("youtube-pause");
    pauseButton.innerText = "❚❚";

    const iframe = wrapper.querySelector("iframe");
    iframe.addEventListener("load", () => {
        const player = new YT.Player(iframe, {
            events: {
                'onStateChange': function (event) {
                    if (event.data === YT.PlayerState.PAUSED) {

                        wrapper.appendChild(pauseButton);
                        pauseButton.addEventListener("click", () => {
                            if (wrapper.contains(pauseButton)) {
                                wrapper.removeChild(pauseButton);
                            }
                            stopAllVideos()
                            player.playVideo();
                        });

                    } else if (event.data === YT.PlayerState.PLAYING) {

                        if (wrapper.contains(pauseButton)) {
                            wrapper.removeChild(pauseButton);
                        }

                    } else if (event.data === YT.PlayerState.ENDED) {

                        player.seekTo(0, true); // Restart video from beginning
                        player.pauseVideo(); // Pause video

                        $(".youtube-slider").slick("slickNext");
                        setTimeout(() => {
                            // player.playVideo(); // Play video again

                        }, 500);
                    }
                }
            }
        });
    });
}