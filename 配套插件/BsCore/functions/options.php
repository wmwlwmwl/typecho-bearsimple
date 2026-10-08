<?php


use Typecho\Db;

const FRAMEWORK_PLUGIN_NAME = 'plugin:BsCore_';
const FRAMEWORK_KEY_PARMAS_NAME = 'plugin:bsOF_key_params';

// ponytail: Typecho 1.3.0 升级脚本把 plugin:/theme: 选项值由 serialize 转为 JSON，此处双格式兼容读取；升级路径：确认不再有 serialize 存量后移除回退分支
if (!function_exists('bs_decode_option_value')) {
    function bs_decode_option_value($value): array
    {
        if (!is_string($value) || $value === '') {
            return array();
        }
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        $unserialized = @unserialize($value);
        return is_array($unserialized) ? $unserialized : array();
    }
}

// ponytail: per-request 选项整包缓存，消除 AssetsDir 等高频调用的重复 SQL；update_option/delete_option 写后失效
if (!function_exists('bs_option_cache')) {
    function bs_option_cache($name, $reset = false): array
    {
        static $cache = array();
        if ($reset || !array_key_exists($name, $cache)) {
            $db = Db::get();
            $row = $db->fetchRow($db->select()->from('table.options')
                ->where('name = ?', $name));
            $cache[$name] = empty($row) ? array() : bs_decode_option_value($row['value']);
        }
        return $cache[$name];
    }
}

if (!function_exists('get_option')) {

// get option for framework
    function get_option($option, $default = false)
    {
        $options = bs_option_cache(FRAMEWORK_PLUGIN_NAME);
        if (array_key_exists($option, $options)) {
            return $options[$option];
        }
        return $default;
    }
}


if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null)
    {
        $db = Db::get();
        $pluginName = FRAMEWORK_PLUGIN_NAME;
        $select = $db->select()->from('table.options')
            ->where('name = ?', $pluginName);

        $options = $db->fetchRow($select);
        $settings = [$option => $value];
        if (empty($options)) {
            $db->query($db->insert('table.options')
                ->rows([
                    'name' => $pluginName,
                    'value' => json_encode($settings),
                    'user' => 0
                ]));
        } else {
            $options = bs_decode_option_value($options['value']);
            $options[$option] = $value;
            $db->query($db->update('table.options')
                ->rows(['value' => json_encode($options)])
                ->where('name = ?', $pluginName)
                ->where('user = ?', 0));
        }

        bs_option_cache($pluginName, true);

        return true;
    }
}
if (!function_exists('update_bs_key_params')) {
    function update_bs_key_params($option, $value, $autoload = null)
    {
        // ponytail: 该选项存储 CSF_Options 对象，必须 serialize 保真；JSON 往返会把对象降级为数组，导致保存时 set_options 致命错误（plugin:BsCore_ 为纯数组才走 JSON）
        $db = Db::get();
        $pluginName = FRAMEWORK_KEY_PARMAS_NAME;
        $select = $db->select()->from('table.options')
            ->where('name = ?', $pluginName);

        $options = $db->fetchRow($select);
        $settings = [$option => $value];
        if (empty($options)) {
            $db->query($db->insert('table.options')
                ->rows([
                    'name' => $pluginName,
                    'value' => serialize($settings),
                    'user' => 0
                ]));
        } else {
            $options = @unserialize($options['value'], ['allowed_classes' => ['CSF_Options']]);
            $options = is_array($options) ? $options : array();
            $options[$option] = $value;
            $db->query($db->update('table.options')
                ->rows(['value' => serialize($options)])
                ->where('name = ?', $pluginName)
                ->where('user = ?', 0));
        }
    }
}
if (!function_exists('get_bs_key_params')) {

// get option for framework
    // ponytail: 读取 CSF_Options 对象须走 unserialize 还原；Typecho 1.3.0 升级脚本可能已把该行转成 JSON（对象信息丢失），此时双格式兼容回退，返回 false 由调用方守卫处理
    function get_bs_key_params($option, $default = false)
    {
        $db = Db::get();
        $row = $db->fetchRow($db->select('value')->from('table.options')
            ->where('name = ?', FRAMEWORK_KEY_PARMAS_NAME));

        if (empty($row) || !is_string($row['value']) || $row['value'] === '') {
            return $default;
        }

        $map = @unserialize($row['value'], ['allowed_classes' => ['CSF_Options']]);
        if (!is_array($map)) {
            $map = bs_decode_option_value($row['value']);
        }

        if (is_array($map) && array_key_exists($option, $map)) {
            return $map[$option];
        }
        return $default;
    }
}
if (!function_exists('delete_option')) {

    function delete_option($option)
    {
        $db = Db::get();
        $pluginName = FRAMEWORK_PLUGIN_NAME;
        if (is_scalar($option)) {
            $option = trim($option);
        }

        if (empty($option)) {
            return false;
        }

        wp_protect_special_option($option);

        // Get the ID, if no ID then return.
        $select = $db->select()->from('table.options')
            ->where('name = ?', $pluginName);

        $row = $db->fetchRow($select);
        if (is_null($row)) {
            return false;
        }

        $result = $db->query($db->delete('table.options')->where('name = ?', 'plugin:' . $pluginName));

        bs_option_cache($pluginName, true);

        if ($result) {

            return true;
        }

        return false;
    }
}
