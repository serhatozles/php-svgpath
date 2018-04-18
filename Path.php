<?php

/**
 * @author Serhat Özleş <serhatozles@gmail.com>
 * @thnx   @aydos
 * @check  javascript version: https://github.com/aydos/svgpath
 */
class Path
{
    
    public $dec = -1;
    public $segs;
    
    /**
     * @param $str
     */
    public function import(string $str)
    {
        $str = preg_replace('/\s/si', ' ', $str);
        $str = trim($str);
        $str = preg_replace('/,/si', ' ', $str);
        $str = preg_replace('/([A-Za-z])([A-Za-z])/si', '$1 $2', $str);
        $str = preg_replace('/([A-Za-z])(\d)/si', '$1 $2', $str);
        $str = preg_replace('/([A-Za-z])(\.)/si', '$1 .', $str);
        $str = preg_replace('/([A-Za-z])(-)/si', '$1 -', $str);
        $str = preg_replace('/(\d)([A-Za-z])/si', '$1 $2', $str);
        $str = preg_replace('/(\d)(-)/si', '$1 -', $str);
        $str = preg_replace('/((?:-?[\d]*)\.\d+)((?:\.\d+)+)/si', '$1 $2', $str);
        $str = preg_replace('/(\s)+/si', ' ', $str);
        
        $list = explode(' ', $str);
        $pret = '';
        $prex = 0;
        $prey = 0;
        $begx = 0;
        $begy = 0;
        $j = 0;
        $i = 0;
        
        while ($i < count($list)) {
            $seg = [
                't'  => null,
                'x'  => null,
                'y'  => null,
                'px' => null,
                'py' => null,
                'x1' => null,
                'y1' => null,
                'x2' => null,
                'y2' => null,
            ];
            
            list(, $ord) = unpack('N', mb_convert_encoding($list[$i], 'UCS-4BE', 'UTF-8'));
            
            if ($ord > 64) {
                $seg['t'] = $list[$i++];
            } else {
                if (empty($pret)) {
                    break;
                }
                $seg['t'] = ($pret === 'M' ? 'L' : ($pret === 'm' ? 'l' : $pret));
            }
            
            $pret = $seg['t'];
            
            switch ($seg['t']) {
                case "Z":
                case "z":
                    $seg['x'] = $begx;
                    $seg['y'] = $begy;
                    break;
                case "M":
                case "L":
                case "H":
                case "V":
                case "T":
                    $seg['x'] = $seg['t'] === "V" ? $prex : $list[$i++];
                    $seg['y'] = $seg['t'] === "H" ? $prey : $list[$i++];
                    $begx = $seg['t'] === "M" ? $seg['x'] : $begx;
                    $begy = $seg['t'] === "M" ? $seg['y'] : $begy;
                    break;
                case "m":
                case "l":
                case "h":
                case "v":
                case "t":
                    $seg['x'] = $seg['t'] === "v" ? $prex : $prex + $list[$i++];
                    $seg['y'] = $seg['t'] === "h" ? $prey : $prey + $list[$i++];
                    $begx = $seg['t'] === "m" ? $seg['x'] : $begx;
                    $begy = $seg['t'] === "m" ? $seg['y'] : $begy;
                    break;
                case "A":
                case "a":
                    $seg['r1'] = $list[$i++];
                    $seg['r2'] = $list[$i++];
                    $seg['ar'] = $list[$i++];
                    $seg['af'] = $list[$i++];
                    $seg['sf'] = $list[$i++];
                    $seg['x'] = $seg['t'] === "A" ? $list[$i++] : $prex + $list[$i++];
                    $seg['y'] = $seg['t'] === "A" ? $list[$i++] : $prey + $list[$i++];
                    break;
                case "C":
                case "Q":
                case "S":
                    $seg['x1'] = $seg['t'] === "S" ? null : $list[$i++];
                    $seg['y1'] = $seg['t'] === "S" ? null : $list[$i++];
                    $seg['x2'] = $seg['t'] === "Q" ? null : $list[$i++];
                    $seg['y2'] = $seg['t'] === "Q" ? null : $list[$i++];
                    $seg['x'] = $list[$i++];
                    $seg['y'] = $list[$i++];
                    break;
                case "c":
                case "q":
                case "s":
                    $seg['x1'] = $seg['t'] === "s" ? null : $prex + $list[$i++];
                    $seg['y1'] = $seg['t'] === "s" ? null : $prey + $list[$i++];
                    $seg['x2'] = $seg['t'] === "q" ? null : $prex + $list[$i++];
                    $seg['y2'] = $seg['t'] === "q" ? null : $prey + $list[$i++];
                    $seg['x'] = $prex + $list[$i++];
                    $seg['y'] = $prey + $list[$i++];
                    break;
                default:
                    $i++;
            }
            
            $seg['px'] = $prex;
            $seg['py'] = $prey;
            $prex = $seg['x'];
            $prey = $seg['y'];
            
            $seg = array_filter($seg, 'strlen');
            $this->segs[$j++] = $seg;
        }
    }
    
    public function export()
    {
        
        $str = '';
        $pre = '';
        for ($i = 0; $i < count($this->segs); $i++) {
            $seg = $this->formatsegment($this->segs[$i]);
            switch ($seg['t']) {
                case "Z":
                case "z":
                    $str .= $seg['t'];
                    break;
                case "M":
                case "m":
                    $str .= $seg['t'] . $seg['x'] . " " . $seg['y'];
                    break;
                case "L":
                    $str .= ($pre == $seg['t'] || $pre == "M") ? " " : "L";
                    $str .= $seg['x'] . " " . $seg['y'];
                    break;
                case "l":
                    $str .= ($pre == $seg['t'] || $pre == "m") ? " " : "l";
                    $str .= $seg['x'] . " " . $seg['y'];
                    break;
                case "H":
                case "h":
                    $str .= $pre == $seg['t'] ? " " : $seg['t'];
                    $str .= $seg['x'];
                    break;
                case "V":
                case "v":
                    $str .= $pre == $seg['t'] ? " " : $seg['t'];
                    $str .= $seg['y'];
                    break;
                case "A":
                case "a":
                    $str .= $pre == $seg['t'] ? " " : $seg['t'];
                    $str .= $seg['r1'] . " " . $seg['r2'] . " " . $seg['ar'] . " " . $seg['af'] . " " . $seg['sf'] . " " . $seg['x'] . " " . $seg['y'];
                    break;
                case "C":
                case "c":
                    $str .= $pre == $seg['t'] ? " " : $seg['t'];
                    $str .= $seg['x1'] . " " . $seg['y1'] . " " . $seg['x2'] . " " . $seg['y2'] . " " . $seg['x'] . " " . $seg['y'];
                    break;
                case "Q":
                case "q":
                    $str .= $pre == $seg['t'] ? " " : $seg['t'];
                    $str .= $seg['x1'] . " " . $seg['y1'] . " " . $seg['x'] . " " . $seg['y'];
                    break;
                case "S":
                case "s":
                    $str .= $pre == $seg['t'] ? " " : $seg['t'];
                    $str .= $seg['x2'] . " " . $seg['y2'] . " " . $seg['x'] . " " . $seg['y'];
                    break;
                case "T":
                case "t":
                    $str .= $pre == $seg['t'] ? " " : $seg['t'];
                    $str .= $seg['x'] . " " . $seg['y'];
                    break;
            }
            $pre = $seg['t'];
        }
        $str = preg_replace('/ -/si', '-', $str);
        $str = preg_replace('/-0\./si', '-.', $str);
        $str = preg_replace('/ 0\./si', ' .', $str);
        $str = preg_replace('/([A-Za-z])0\./si', '$1.', $str);
        $str = preg_replace('/(\.\d+) \./si', '$1.', $str);
        
        return $str;
    }
    
    
    // make all segments absolute
    public function absolute()
    {
        for ($i = 0; $i < count($this->segs); $i++) {
            $this->segs[$i]['t'] = mb_strtoupper($this->segs[$i]['t']);
        }
    }
    
    // make all segments relative
    public function relative()
    {
        for ($i = 0; $i < count($this->segs); $i++) {
            $this->segs[$i]['t'] = mb_strtolower($this->segs[$i]['t']);
        }
    }
    
    // set the global dec variable, to rounding decimals
    public function round($d)
    {
        if (!is_numeric($d)) {
            $d = 0;
        }
        if ($d < 0) {
            $d = -1;
        }
        $this->dec = floor($d);
    }
    
    public function rounddec($num)
    {
        if ($this->dec < 0) {
            return $num;
        }
        if (fmod($num, 1) === 0) {
            return $num;
        } elseif ($this->dec == 0) {
            return round($num);
        } else {
            $pow = pow(10, $this->dec);
            
            return round($num * $pow) / $pow;
        }
    }
    
    public function formatsegment($s)
    {
        
        list(, $ord) = unpack('N', mb_convert_encoding($s['t'], 'UCS-4BE', 'UTF-8'));
        
        $seg = [
            'af'   => null,
            'ar'   => null,
            'info' => null,
            'px'   => null,
            'py'   => null,
            'r1'   => null,
            'r2'   => null,
            'sf'   => null,
            't'    => null,
            'x'    => null,
            'x1'   => null,
            'x2'   => null,
            'y'    => null,
            'y1'   => null,
            'y2'   => null,
        ];
        $seg['t'] = $s['t'];
        $seg['x'] = $ord < 96 ? $this->rounddec($s['x']) : $this->rounddec(($s['x'] - $s['px']));
        $seg['y'] = $ord < 96 ? $this->rounddec($s['y']) : $this->rounddec($s['y'] - $s['py']);
        $seg['px'] = $this->rounddec($s['px']);
        $seg['py'] = $this->rounddec($s['py']);
        $seg['x1'] = (!isset($s['x1']) ? null : ($ord < 96 ? $this->rounddec($s['x1']) : $this->rounddec($s['x1'] - $s['px'])));
        $seg['y1'] = (!isset($s['y1']) ? null : ($ord < 96 ? $this->rounddec($s['y1']) : $this->rounddec($s['y1'] - $s['py'])));
        $seg['x2'] = (!isset($s['x2']) ? null : ($ord < 96 ? $this->rounddec($s['x2']) : $this->rounddec($s['x2'] - $s['px'])));
        $seg['y2'] = (!isset($s['y2']) ? null : ($ord < 96 ? $this->rounddec($s['y2']) : $this->rounddec($s['y2'] - $s['py'])));
        $seg['r1'] = !isset($s['r1']) ? null : $this->rounddec($s['r1']);
        $seg['r2'] = !isset($s['r1']) ? null : $this->rounddec($s['r2']);
        $seg['ar'] = !isset($s['ar']) ? null : $this->rounddec($s['ar']);
        $seg['af'] = $s['af'];
        $seg['sf'] = $s['sf'];
        $seg['info'] = $s['info'];
        if ($s['t'] == "M") {
            $seg['info'] .= "m " . $this->rounddec($s['x'] - $s['px']) . " " . $this->rounddec($s['y'] - $s['py']);
        }
        if ($s['t'] == "m") {
            $seg['info'] .= "M " . $this->rounddec($s['x']) . " " . $this->rounddec($s['y']);
        }
        
        return $seg;
    }
    
    public function move($dx, $dy)
    {
        for ($i = 0; $i < count($this->segs); $i++) {
            $this->segs[$i]['x'] += $dx;
            $this->segs[$i]['y'] += $dy;
            $this->segs[$i]['px'] += $dx;
            $this->segs[$i]['py'] += $dy;
            $this->segs[$i]['x1'] = !isset($this->segs[$i]['x1']) ? null : $this->segs[$i]['x1'] + $dx;
            $this->segs[$i]['y1'] = !isset($this->segs[$i]['y1']) ? null : $this->segs[$i]['y1'] + $dy;
            $this->segs[$i]['x2'] = !isset($this->segs[$i]['x2']) ? null : $this->segs[$i]['x2'] + $dx;
            $this->segs[$i]['y2'] = !isset($this->segs[$i]['y2']) ? null : $this->segs[$i]['y2'] + $dy;
        }
        $this->segs[0]['px'] = 0;
        $this->segs[0]['py'] = 0;
    }
    
    // flip horizontally with flip(undefined, center)
    // flip vertically, with flip(center, undefined)
    // flip wrt a point (px, py)
    public function flip($x, $y)
    {
        for ($i = 0; $i < count($this->segs); $i++) {
            if (isset($x)) {
                $this->segs[$i]['x'] = $x + ($x - $this->segs[$i]['x']);
                $this->segs[$i]['px'] = $x + ($x - $this->segs[$i]['px']);
                $this->segs[$i]['x1'] = !isset($this->segs[$i]['x1']) ? null : $x + ($x - $this->segs[$i]['x1']);
                $this->segs[$i]['x2'] = !isset($this->segs[$i]['x2']) ? null : $x + ($x - $this->segs[$i]['x2']);
                $this->segs[$i]['sf'] = !isset($this->segs[$i]['sf']) ? null : fmod(($this->segs[$i]['sf'] + 1), 2);
            }
            if (isset($y)) {
                $this->segs[$i]['y'] = $y + ($y - $this->segs[$i]['y']);
                $this->segs[$i]['py'] = $y + ($y - $this->segs[$i]['py']);
                $this->segs[$i]['y1'] = !isset($this->segs[$i]['y1']) ? null : $y + ($y - $this->segs[$i]['y1']);
                $this->segs[$i]['y2'] = !isset($this->segs[$i]['y2']) ? null : $y + ($y - $this->segs[$i]['y2']);
                $this->segs[$i]['sf'] = !isset($this->segs[$i]['sf']) ? null : fmod(($this->segs[$i]['sf'] + 1), 2);
            }
        }
        $this->segs[0]['px'] = 0;
        $this->segs[0]['py'] = 0;
    }
    
    public function center($x, $y)
    {
        $minx = $this->segs[0]['x'];
        $miny = $this->segs[0]['y'];
        $maxx = $this->segs[0]['x'];
        $maxy = $this->segs[0]['y'];
        for ($i = 0; $i < count($this->segs); $i++) {
            $minx = $this->segs[$i]['x'] < $minx ? $this->segs[$i]['x'] : $minx;
            $miny = $this->segs[$i]['y'] < $miny ? $this->segs[$i]['y'] : $miny;
            $maxx = $this->segs[$i]['x'] > $maxx ? $this->segs[$i]['x'] : $maxx;
            $maxy = $this->segs[$i]['y'] > $maxy ? $this->segs[$i]['y'] : $maxy;
        }
        $dx = $x - $minx - ($maxx - $minx) / 2;
        $dy = $y - $miny - ($maxy - $miny) / 2;
        $this->move($dx, $dy);
    }
    
    public function scale($ratio)
    {
        if (!is_numeric($ratio)) {
            return false;
        }
        if ($ratio <= 0) {
            return false;
        }
        for ($i = 0; $i < count($this->segs); $i++) {
            $seg = $this->segs[$i];
            $seg['x'] *= $ratio;
            $seg['y'] *= $ratio;
            $seg['px'] *= $ratio;
            $seg['py'] *= $ratio;
            $seg['x1'] = !isset($s['x1']) ? null : $ratio * $seg['x1'];
            $seg['y1'] = !isset($s['y1']) ? null : $ratio * $seg['y1'];
            $seg['x2'] = !isset($s['x2']) ? null : $ratio * $seg['x2'];
            $seg['y2'] = !isset($s['y2']) ? null : $ratio * $seg['y2'];
            $seg['r1'] = !isset($s['r1']) ? null : $ratio * $seg['r1'];
            $seg['r2'] = !isset($s['r2']) ? null : $ratio * $seg['r2'];
        }
        
        return $seg;
    }
    
    public function rotate($x, $y, $d)
    {
        $d *= pi() / 180;
        $sin = sin($d);
        $cos = cos($d);
        for ($i = 0; $i < count($this->segs); $i++) {
            $rp = $this->rotatepoint($this->segs[$i]['x'], $this->segs[$i]['y'], $x, $y, $sin, $cos);
            $this->segs[$i]['x'] = $rp[0];
            $this->segs[$i]['y'] = $rp[1];
            $rp = $this->rotatepoint($this->segs[$i]['px'], $this->segs[$i]['py'], $x, $y, $sin, $cos);
            $this->segs[$i]['px'] = $rp[0];
            $this->segs[$i]['py'] = $rp[1];
            if (isset($this->segs[$i]['x1'])) {
                $rp = $this->rotatepoint($this->segs[$i]['x1'], $this->segs[$i]['y1'], $x, $y, $sin, $cos);
                $this->segs[$i]['x1'] = $rp[0];
                $this->segs[$i]['y1'] = $rp[1];
            }
            if (isset($this->segs[$i]['x2'])) {
                $rp = $this->rotatepoint($this->segs[$i]['x2'], $this->segs[$i]['y2'], $x, $y, $sin, $cos);
                $this->segs[$i]['x2'] = $rp[0];
                $this->segs[$i]['y2'] = $rp[1];
            }
            if ($this->segs[$i]['t'] == "H" || $this->segs[$i]['t'] == 'V') {
                $this->segs[$i]['t'] = 'L';
            }
            if ($this->segs[$i]['t'] == 'h' || $this->segs[$i]['t'] == 'v') {
                $this->segs[$i]['t'] = 'l';
            }
        }
        $this->segs[0]['px'] = 0;
        $this->segs[0]['py'] = 0;
    }
    
    public function rotatepoint($px, $py, $ox, $oy, $sin, $cos)
    {
        $x = $cos * ($px - $ox) - $sin * ($py - $oy) + $ox;
        $y = $sin * ($px - $ox) + $cos * ($py - $oy) + $oy;
        
        return [$x, $y];
    }
}
