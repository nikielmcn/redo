var AccessSniff = require('access-sniff');
var chalk = require('chalk');
var fs = require('fs');
var gulp = require('gulp');
var gutil = require('gulp-util');
var lintReporter = require('gulp-tslint-jenkins-reporter');
var path = require('path');
var TemplateLintConfig = require('aurelia-template-lint').Config;
var templateLinter = require('gulp-aurelia-template-lint');
var tslint = require('gulp-tslint');
var htmllint = require('gulp-htmllint');

var typingsLinter = require('../typings-linter');
var paths = require('../paths');

// TODO Restore lint-aurelia-templates when issue is resolved: https://github.com/MeirionHughes/aurelia-template-lint/issues/178
// gulp.task('lint', ['lint-ts', 'lint-aurelia-templates', 'lint-typings']);
gulp.task('lint', ['lint-ts', 'lint-typings', 'lint-html']);

gulp.task('lint-ts', () => {
  return gulp.src(paths.scripts[0])
    .pipe(tslint({
      formatter: "verbose",
      configuration: "build/tslint.json"
    }))
    .pipe(lintReporter({
      filename: path.join(paths.lintReports, 'ts.xml')
    }))
    .pipe(tslint.report());
});

gulp.task('lint-wcag', () => {
  return AccessSniff
    .default([paths.html, '../Repeka/**/*.html.twig'], {
      force: true,
      verbose: false,
      ignore: [
        'WCAG2A.Principle2.Guideline2_4.2_4_2.H25.1.NoTitleEl',
        'WCAG2A.Principle3.Guideline3_1.3_1_1.H57.2'
      ]
    })
    .then(function (lintErrors) {
      if (Object.keys(lintErrors).length > 0) {
        var xml = lintErrorsToXml(lintErrors, (error) => {
          return {
            line: error.position.lineNumber,
            column: error.position.columnNumber,
            message: error.description,
            source: error.issue,
            severity: 'warning'
          };
        }, file => process.cwd() + (file[0] == '/' ? '' : '/') + file);
        fs.writeFileSync(path.join(paths.lintReports, 'wcag.xml'), xml);
        throw 'There are some WCAG rules violations';
      }
    });
});

gulp.task('lint-aurelia-templates', () => {
  var lintErrors = {};
  var config = new TemplateLintConfig();
  config.useRuleAureliaBindingAccess = true;
  config.reflectionOpts.sourceFileGlob = paths.scripts[0];
  return gulp.src(paths.html)
    .pipe(templateLinter(config, (error, file) => {
      if (!lintErrors[file]) {
        lintErrors[file] = [];
      }
      lintErrors[file].push(error);
      gutil.log(chalk.red(`${error.message} Line ${error.line} Col ${error.column}`), file);
    }))
    .on('end', () => {
      if (Object.keys(lintErrors).length > 0) {
        var xml = lintErrorsToXml(lintErrors, (error) => {
          error.source = 'aurelia-template-lint';
          error.severity = 'error';
          return error;
        });
        fs.writeFileSync(path.join(paths.lintReports, 'aurelia-template.xml'), xml);
        throw 'There are some aurelia-template-lint errors';
      }
    });
});

gulp.task('lint-typings', (done) => {
  typingsLinter('typings.json', 'jspm.config.js', done);
});

gulp.task('lint-html', () => {
  var lintErrors = {};
  return gulp.src(paths.html)
    .pipe(htmllint({
      config: path.join(__dirname, '../htmllint.json')
    }, (filepath, issues) => {
      if (issues.length > 0) {
        lintErrors[filepath] = issues;
        issues.forEach((issue) => console.log(filepath + ' [' + issue.line + ',' + issue.column + ']: ' + '(' + issue.code + ') ' + issue.msg));
      }
    }))
    .on('end', () => {
      if (Object.keys(lintErrors).length > 0) {
        var xml = lintErrorsToXml(lintErrors, (error) => {
          error.source = 'htmllint';
          error.severity = 'error';
          error.message = error.msg;
          return error;
        });
        fs.writeFileSync(path.join(paths.lintReports, 'htmllint.xml'), xml);

        throw 'There are some htmllint errors';
      }
    });
});

function lintErrorsToXml(lintErrors, errorExtractor, filePathBuilder = f => f) {
  let xml = '<?xml version="1.0" encoding="utf-8"?><checkstyle version="4.3">';
  for (let file in lintErrors) {
    const filepath = filePathBuilder(file);
    xml += '<file name="' + filepath + '">';
    for (let i = 0; i < lintErrors[file].length; i++) {
      const e = errorExtractor(lintErrors[file][i]);
      xml += `<error line="${e.line || 1}" column="${e.column || 1}" severity="${e.severity}" message="${e.message}" source="${e.source.replace(/\./g, '/')}"/>`;
    }
    xml += '</file>';
  }
  xml += '</checkstyle>';
  return xml;
}
