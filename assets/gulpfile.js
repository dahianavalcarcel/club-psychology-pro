const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const postcss = require('gulp-postcss');
const autoprefixer = require('autoprefixer');
const cssnano = require('cssnano');
const concat = require('gulp-concat');
const uglify = require('gulp-uglify');
const babel = require('gulp-babel');
const sourcemaps = require('gulp-sourcemaps');
const imagemin = require('gulp-imagemin');
const newer = require('gulp-newer');
const del = require('del');
const browserSync = require('browser-sync').create();
const plumber = require('gulp-plumber');
const notify = require('gulp-notify');
const rename = require('gulp-rename');

// Paths
const paths = {
  scss: {
    src: 'src/scss/**/*.scss',
    dest: 'dist/css/'
  },
  js: {
    src: 'src/js/**/*.js',
    dest: 'dist/js/'
  },
  images: {
    src: 'src/images/**/*',
    dest: 'dist/images/'
  },
  fonts: {
    src: 'src/fonts/**/*',
    dest: 'dist/fonts/'
  }
};

// Environment
const isProduction = process.env.NODE_ENV === 'production';

// Error handler
const errorHandler = {
  errorHandler: notify.onError({
    title: 'Gulp Error',
    message: '<%= error.message %>'
  })
};

// Clean task
function clean() {
  return del(['dist/**', '!dist']);
}

// SCSS compilation
function styles() {
  return gulp.src([
      'src/scss/frontend.scss',
      'src/scss/admin.scss',
      'src/scss/test-form.scss',
      'src/scss/result-viewer.scss',
      'src/scss/user-panel.scss'
    ])
    .pipe(plumber(errorHandler))
    .pipe(sourcemaps.init())
    .pipe(sass({
      outputStyle: isProduction ? 'compressed' : 'expanded',
      includePaths: ['node_modules']
    }))
    .pipe(postcss([
      autoprefixer(),
      ...(isProduction ? [cssnano()] : [])
    ]))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(paths.scss.dest))
    .pipe(browserSync.stream());
}

// Main JavaScript files
function scripts() {
  return gulp.src([
      'src/js/frontend.js',
      'src/js/admin.js',
      'src/js/test-form.js',
      'src/js/result-viewer.js',
      'src/js/user-panel.js'
    ])
    .pipe(plumber(errorHandler))
    .pipe(sourcemaps.init())
    .pipe(babel({
      presets: [
        ['@babel/preset-env', {
          targets: {
            browsers: ['> 1%', 'last 2 versions', 'ie >= 11']
          }
        }]
      ]
    }))
    .pipe(isProduction ? uglify() : gulp.src.pass())
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(paths.js.dest))
    .pipe(browserSync.stream());
}

// Vendor JavaScript (libraries)
function vendorScripts() {
  return gulp.src([
      'node_modules/chart.js/dist/chart.min.js',
      'node_modules/tippy.js/dist/tippy-bundle.umd.min.js',
      'node_modules/swiper/swiper-bundle.min.js'
    ])
    .pipe(concat('vendors.js'))
    .pipe(gulp.dest(paths.js.dest));
}

// Image optimization
function images() {
  return gulp.src(paths.images.src)
    .pipe(newer(paths.images.dest))
    .pipe(imagemin([
      imagemin.gifsicle({ interlaced: true }),
      imagemin.mozjpeg({ quality: 80, progressive: true }),
      imagemin.optipng({ optimizationLevel: 5 }),
      imagemin.svgo({
        plugins: [
          { removeViewBox: false },
          { cleanupIDs: false }
        ]
      })
    ]))
    .pipe(gulp.dest(paths.images.dest));
}

// Copy fonts
function fonts() {
  return gulp.src(paths.fonts.src)
    .pipe(newer(paths.fonts.dest))
    .pipe(gulp.dest(paths.fonts.dest));
}

// Watch files
function watch() {
  gulp.watch(paths.scss.src, styles);
  gulp.watch(paths.js.src, scripts);
  gulp.watch(paths.images.src, images);
  gulp.watch(paths.fonts.src, fonts);
  
  // Watch PHP files for browser sync
  gulp.watch('../../**/*.php').on('change', browserSync.reload);
}

// Browser Sync
function serve() {
  browserSync.init({
    proxy: 'http://localhost:8000', // Adjust to your local WordPress URL
    files: [
      '../../**/*.php',
      'dist/**/*'
    ],
    open: false,
    notify: false
  });
}

// Component-specific tasks
function adminStyles() {
  return gulp.src('src/scss/admin.scss')
    .pipe(plumber(errorHandler))
    .pipe(sourcemaps.init())
    .pipe(sass({
      outputStyle: isProduction ? 'compressed' : 'expanded'
    }))
    .pipe(postcss([
      autoprefixer(),
      ...(isProduction ? [cssnano()] : [])
    ]))
    .pipe(rename('admin.css'))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(paths.scss.dest));
}

function frontendStyles() {
  return gulp.src('src/scss/frontend.scss')
    .pipe(plumber(errorHandler))
    .pipe(sourcemaps.init())
    .pipe(sass({
      outputStyle: isProduction ? 'compressed' : 'expanded'
    }))
    .pipe(postcss([
      autoprefixer(),
      ...(isProduction ? [cssnano()] : [])
    ]))
    .pipe(rename('frontend.css'))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(paths.scss.dest));
}

function testFormStyles() {
  return gulp.src('src/scss/test-form.scss')
    .pipe(plumber(errorHandler))
    .pipe(sourcemaps.init())
    .pipe(sass({
      outputStyle: isProduction ? 'compressed' : 'expanded'
    }))
    .pipe(postcss([
      autoprefixer(),
      ...(isProduction ? [cssnano()] : [])
    ]))
    .pipe(rename('test-form.css'))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(paths.scss.dest));
}

// Build task
const build = gulp.series(
  clean,
  gulp.parallel(
    styles,
    scripts,
    vendorScripts,
    images,
    fonts
  )
);

// Development task
const dev = gulp.series(
  build,
  gulp.parallel(watch, serve)
);

// Production build
const prod = gulp.series(build);

// Component builds
const buildAdmin = gulp.parallel(adminStyles);
const buildFrontend = gulp.parallel(frontendStyles);
const buildTestForm = gulp.parallel(testFormStyles);

// Linting tasks
function stylelint() {
  const gulpStylelint = require('gulp-stylelint');
  
  return gulp.src('src/scss/**/*.scss')
    .pipe(gulpStylelint({
      reporters: [
        { formatter: 'string', console: true }
      ]
    }));
}

function eslint() {
  const gulpEslint = require('gulp-eslint');
  
  return gulp.src('src/js/**/*.js')
    .pipe(gulpEslint())
    .pipe(gulpEslint.format())
    .pipe(gulpEslint.failAfterError());
}

// Deploy task (minified assets)
function deploy() {
  // Copy minified files to a deploy directory
  return gulp.src([
      'dist/**/*',
      '!dist/**/*.map'
    ])
    .pipe(gulp.dest('../deploy/assets/'));
}

// Generate critical CSS
function criticalCSS() {
  const critical = require('critical');
  
  return critical.generate({
    inline: true,
    base: 'dist/',
    src: 'index.html', // Adjust as needed
    dest: 'css/critical.css',
    dimensions: [
      { width: 320, height: 480 },
      { width: 768, height: 1024 },
      { width: 1280, height: 960 }
    ]
  });
}

// Asset revision (cache busting)
function revision() {
  const rev = require('gulp-rev');
  const revRewrite = require('gulp-rev-rewrite');
  
  return gulp.src(['dist/**/*.css', 'dist/**/*.js'])
    .pipe(rev())
    .pipe(gulp.dest('dist/'))
    .pipe(rev.manifest())
    .pipe(gulp.dest('dist/'));
}

// Security checks
function auditSecurity() {
  const { exec } = require('child_process');
  
  return new Promise((resolve, reject) => {
    exec('npm audit', (error, stdout, stderr) => {
      if (error) {
        console.log('Security audit found issues:', stdout);
      } else {
        console.log('Security audit passed');
      }
      resolve();
    });
  });
}

// Performance budget check
function performanceBudget() {
  const size = require('gulp-size');
  
  return gulp.src('dist/**/*')
    .pipe(size({
      showFiles: true,
      showTotal: true,
      title: 'Build Size'
    }))
    .pipe(size({
      showFiles: false,
      gzip: true,
      title: 'Gzipped Size'
    }));
}

// Archive build
function archive() {
  const zip = require('gulp-zip');
  const timestamp = new Date().toISOString().slice(0, 10);
  
  return gulp.src('dist/**/*')
    .pipe(zip(`club-psychology-pro-assets-${timestamp}.zip`))
    .pipe(gulp.dest('archives/'));
}

// Export tasks
exports.clean = clean;
exports.styles = styles;
exports.scripts = scripts;
exports.vendorScripts = vendorScripts;
exports.images = images;
exports.fonts = fonts;
exports.watch = watch;
exports