module.exports = function (grunt) {

	'use strict';

	// Project configuration.
	grunt.initConfig({
		pkg: '<json:package.json>',
		meta: {
			banner: '/*! <%= pkg.name %> - v<%= pkg.version %> - ' +
			'<%= grunt.template.today("yyyy-mm-dd") %> */'
		},
		lint: {
			all: [
				'Gruntfile.js',
				'Resources/Public/JavaScript/*.js',
				'Resources/Public/JavaScript/modeller/models/**/*.js',
				'Resources/Public/JavaScript/modeller/views/**/*.js',
				'Resources/Public/JavaScript/modeller/init.js',
				'Resources/Public/JavaScript/panels/**/*.js',
				'Resources/Public/JavaScript/view/**/*.js'
			],
			options: {
				options: '<json:.jshintrc>',
			}
		},
		concat: {
			deploy: {
				src: [],
				dest: 'public/js/main-<%= pkg.version %>.min.js'
			}
		},
		rubysass: {
			dev: {
				options: {
					unixNewlines: true,
					style: 'expanded'
				},
				files: {
					'Resources/Public/Css/Modeller.css': 'Resources/Private/Sass/Modeller.scss'
				}
			},
			deploy: {
				options: {
					style: 'compressed'
				},
				files: {
					'public/css/main-<%= pkg.version %>.min.css': 'theme/css/main.scss'
				}

			}
		},
		min: {
			deploy: {
				src: ['<banner>', '<config:concat.deploy.dest>'],
				dest: 'public/js/main-<%= pkg.version %>.min.js'
			}
		},

		pngmin: {
			src: ['theme/img/*.png'],
			dest: 'public'
		},
		gifmin: {
			src: ['theme/img/*.gif'],
			dest: 'public'
		},
		jpgmin: {
			src: ['theme/img/*.jpg'],
			dest: 'public'
		},

		copy: {
			deploy: {
				files: {
					'public/img': 'theme/img/*.svg'
				}
			}
		},

		watch: {
			css: {
				files: ['Resources/Private/Sass/**/*.scss'],
				tasks: 'rubysass:dev'
			},
			js: {
				files: '<config:lint.all>',
				tasks: 'lint'
			}
		}
	});

	// Load some stuff
	grunt.loadNpmTasks('grunt-sass');
	grunt.loadNpmTasks('grunt-imagine');

	// Load tasks and helpers from the "grunt-tasks" directory
	grunt.loadTasks('grunt-tasks');

	// Default task.
	grunt.registerTask('default', 'lint concat rubysass:dev min');

	// Default task.
	grunt.registerTask('deploy', 'lint concat:deploy rubysass:deploy min:deploy pngmin gifmin jpgmin copy');

};

