/*globals module */
module.exports = function (grunt) {

	// Project configuration.
	grunt.initConfig({
		pkg: '<json:package.json>',
		meta: {
			banner: '/*! <%= pkg.name %> - v<%= pkg.version %> - ' +
			'<%= grunt.template.today("yyyy-mm-dd") %> */'
		},
		lint: {
			all: [
				'grunt.js',
				'Resources/Public/JavaScript/jsplumb/init.js',
				'Resources/Public/JavaScript/panels/*.js',
				'Resources/Public/JavaScript/view/*.js'
			]
		},
		jshint: {
			options: {
				curly: true,
				eqeqeq: true,
				immed: true,
				latedef: true,
				newcap: true,
				noarg: true,
				sub: true,
				undef: true,
				boss: true,
				eqnull: true,
				browser: true
			},
			globals: {
				jQuery: true,
				$: true,
				TYPO3: true
			}
		},
		sass: {
			css: {
				src: 'Resources/Private/Sass/Modeller.scss',
				dest: 'Resources/Public/Css/Modeller.css'
			}
		},
		cssmin: {
			css: {
				src: '<config:sass.css.dest>',
				dest: '<config:sass.css.dest>'
			}
		},

		watch: {
			scripts: {
				files: '<config:lint.all>',
				tasks: 'lint'
			},
			css: {
				files: '<config:sass.css.src>',
				tasks: 'sass'
			}
		}
	});

	// Load some stuff
	grunt.loadNpmTasks('grunt-css');
	grunt.loadNpmTasks('grunt-sass');

	// Default task.
	grunt.registerTask('default', 'lint sass cssmin');

};
