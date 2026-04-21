const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const cleanCSS = require('gulp-clean-css');
const postcss = require('gulp-postcss');
const autoprefixer = require('autoprefixer');
const sourcemaps = require('gulp-sourcemaps');
const cache = require('gulp-cache');
const { exec } = require('child_process');

// Paths
const paths = {
    styles: {
        src: 'assets/src/scss/**/*.scss',
        dest: 'assets/dist/css/'
    },
    scripts: {
        src: 'assets/src/js/**/*.js',
        dest: 'assets/dist/js/'
    }
};

// Compile SCSS
function styles() {
    return gulp
        .src(paths.styles.src, { sourcemaps: true })
        .pipe(sourcemaps.init())
        .pipe(
            sass({ includePaths: ['node_modules'] }).on('error', sass.logError)
        )
        .pipe(postcss([autoprefixer()]))
        .pipe(cleanCSS())
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(paths.styles.dest));
}

// One-time Webpack build when JS changes
function scripts(cb) {
    exec('npx webpack', (err, stdout, stderr) => {
        console.log('Webpack output:\n', stdout);
        if (stderr) console.error('Webpack errors:\n', stderr);
        cb(err);
    });
}

// Clear gulp cache
function clearCache(done) {
    return cache.clearAll(done);
}

// Watch SCSS and JS
function watchFiles() {
    gulp.watch(paths.styles.src, styles);
    gulp.watch(paths.scripts.src, scripts);
}

// Exported tasks
exports.clearCache = clearCache;
exports.styles = styles;
exports.scripts = scripts;
exports.default = gulp.series(gulp.parallel(styles, scripts, clearCache), watchFiles);
