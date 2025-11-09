const deepScan = require('deep-scan');
const path = require('path');
const UglifyJS = require("uglify-js");
const fs = require("fs");
const { match } = require('assert');

// Scan all js files in assets/js dir to minifiy in public/build/js (except files marked as webpack-able with "webpack." prefix)
let asset_path = path.resolve(__dirname, 'assets/js');
let build_path = path.resolve(__dirname, 'public/build/js');
// remove build folder first (to empty it, will be re-created later)
fs.rmSync(build_path, { recursive: true, force: true });
deepScan(
	// First parameter : path
	asset_path,
	// Second parameter : file callback
	filepath => { 
		let fileName = path.relative(asset_path,filepath);
		console.log('processing : ' + fileName);
		let source_file = filepath;
		let dest_file = build_path+'/'+fileName;
		let dest_file_detail = path.parse(dest_file);
		// create subdirs in build if necessary
		if (!fs.existsSync(dest_file_detail.dir)){
			fs.mkdirSync(dest_file_detail.dir, { recursive: true });
		}
		// read, uglify and write file
		fs.readFile(source_file, 'utf8', function(err, data) {
			if (err) {
				console.error(err);
			}
			let uglified;
			if(dest_file_detail.name.match(/^no_minify\./)){
				// no_minify
				uglified = {
					code: data
				};
			} else {
				uglified = UglifyJS.minify(data,{
					sourceMap: {
						filename: dest_file_detail.name+'.min.js',
						url: dest_file_detail.name+'.min.js.map'
					}
				});
			}
			
			if(typeof uglified.error === 'undefined'){
				// write minified file
				fs.writeFile(dest_file_detail.dir+'/'+dest_file_detail.name+'.min.js', uglified.code, err => {
					if (err) {
						console.error(err);
					}
				});
				if(typeof uglified.map !== 'undefined'){
					// write map file
					fs.writeFile(dest_file_detail.dir+'/'+dest_file_detail.name+'.min.js.map', uglified.map, err => {
						if (err) {
							console.error(err);
						}
					});
				}
			} else {
				console.error(uglified.error);
			}
		});
	},
	// Regex rules array (get all js files not starting with 'webpack.' and ending with '.js'
	['^(?!webpack\.)(.+)\\.js$'],
	// Type of filter (only in our case)
	'ONLY'
);
