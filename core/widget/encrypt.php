<?php
header("HTTP/1.1 200 OK");
header("Access-Control-Allow-Origin: *");
date_default_timezone_set('PRC');

if (bs_same_origin_check() && @$_POST['action'] == 'open_lock') {
						if (!empty($_POST['password'])) {
							$password = $_POST['password'];
							$md5 = $_POST['encryptpassword'];
							$type = $_POST['type'];
							$options = bsOptions::getInstance()::get_option('bearsimple');
							if (md5Encode($password) == $md5) {
								$result = array('code' => '1');
								if ($type == 'category') {
									$category = $_POST['category'];
									// ponytail: category 会拼入 Cookie 键名，白名单过滤
									if (!is_string($category) || !preg_match('/^[\w-]+$/u', $category)) {
										exit(json_encode(array('code' => '-2'), JSON_UNESCAPED_UNICODE));
									}
									\Typecho\Cookie::set('category_' . $category, md5Encode($password));
								}
							} else {
								$result = array('code' => '-1');
							}
						} else {
							$result = array('code' => '-2');
						}
						exit(json_encode($result,JSON_UNESCAPED_UNICODE));
					}