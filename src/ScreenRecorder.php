<?php
/**
 * Created by PhpStorm.
 * User: dawood.ikhlaq
 * Date: 7/17/2017
 * Time: 12:43
 */

namespace dawood\PhpScreenRecorder;

use Symfony\Component\Process\Process;

class ScreenRecorder
{
    private $pathToSaveVideo;
    private $binary;
    private $options;
    private $process;
    private $command;

    /**
     * set the basic options to record the screen these options can be replaced
     * with your desired options using setOptions method
     * also initialize the temporary path to save videos
     * in case you don't provide one
     * ScreenRecorder constructor.
     */
    function __construct()
    {
        $this->setBinaryForCurrentOs();
        $this->pathToSaveVideo=sys_get_temp_dir().DIRECTORY_SEPARATOR.md5(time()).".flv";
        $this->options=[
            '-f'=>'gdigrab',
            '-show_region'=>'1',
            '-draw_mouse'=>'1',
            '-framerate'=>'60',
            '-video_size'=>'1080x768',
            '-offset_x'=>'0',
            '-offset_y'=>'0',
            '-i'=>'desktop',
            '-b:v'=>'8000k',
            '-vcodec'=>'h264',
            '-y'=>'',
        ];
    }

    /**
     * merge provided options to the default options
     * can set also new options
     * @param array $options
     * @author Dawood Ikhlaq <daudmalik06@gmail.com>
     */
    public function setOptions(array $options=[])
    {
        $arguments=array_merge($this->options,$options);
        $this->command=[$this->binary];
        foreach ($arguments as $option=>$value)
        {
            $argumentsForBinary[]=' '.trim($option).' '.trim($value);
            if (!empty($option))
                $this->command[]=trim($option);
            if ($value !== '')
                $this->command[]=trim($value);
        }
        $this->options=$arguments;
    }

    /**
     * set the screen size to capture
     * @param string|null $width
     * @param string|null $height
     * @throws \Exception
     * @author Dawood Ikhlaq <daudmalik06@gmail.com>
     */
    public function setScreenSizeToCapture($width=null, $height=null)
    {
        if(!$width || !$height)
        {
            throw new \Exception("please provide valid width or height");
        }
        $this->setOptions(['-video_size'=>$width.'x'.$height]);
    }

    /**
     * set the capture size to full screen
     * @throws \Exception
     * @author Jugal Kariya <jugalkariya@gmail.com>
     */
    public function setFullScreenCapture()
    {
        unset($this->options['-video_size']);
    }

    /**
     * starts the recording
     * if $pathToSaveVideo is provided
     * the video will be recorded on that place
     * else it will save in temporary folder of Operating system
     * @param string|null $pathToSaveVideo
     * @param int $sleep number of seconds to sleep after starting the process
     * @author Dawood Ikhlaq <daudmalik06@gmail.com>
     */
    public function startRecording($pathToSaveVideo=null,$sleep=2)
    {
        $this->pathToSaveVideo=$pathToSaveVideo?$pathToSaveVideo.(strstr($pathToSaveVideo,'.flv')?'':'.flv'):$this->pathToSaveVideo;
        $this->deleteFileIfExist($this->pathToSaveVideo);
        $this->setOptions([$this->pathToSaveVideo]);
        $this->process=new Process($this->command);
        $this->process->start();
        sleep($sleep);
    }

    /**
     * stop the recording
     * @author Dawood Ikhlaq <daudmalik06@gmail.com>
     * @param int $sleep number of seconds to sleep after stopping the process
     * @throws \Exception
     */
    public function stopRecording($sleep=2)
    {
        sleep($sleep);
        if(!$this->process->getPid())
        {
            throw new \Exception("Some error occurred during recording, verify your provided options");
        }
        $this->process->signal(9);
        unset($this->process);
    }

    /**
     * @return string the saved video file path
     * @author Dawood Ikhlaq <daudmalik06@gmail.com>
     */
    public function getVideo()
    {
        return $this->pathToSaveVideo;
    }

    /**
     * deletes the provided file if exist
     * @param string $file
     * @author Dawood Ikhlaq <daudmalik06@gmail.com>
     */
    private function deleteFileIfExist($file)
    {
        if(file_exists($file))
        {
            @unlink($file);
        }
    }

    /**
     * set the binary of ffmpeg to run depending on the
     * operating system , it will pick from the bin folder
     * @author Dawood Ikhlaq <daudmalik06@gmail.com>
     */
    private function setBinaryForCurrentOs()
    {
        $ds=DIRECTORY_SEPARATOR;
        if(strcasecmp(substr(PHP_OS, 0, 3), 'WIN') == 0)
        {
            $this->setBinary(__DIR__.$ds."bin".$ds."ffmpegWindows.exe");
        }elseif(stristr(PHP_OS, 'darwin'))
        {
            $this->setBinary(__DIR__.$ds."bin".$ds."ffmpegLinux");

        } elseif(stristr(PHP_OS, 'linux'))
        {
            $this->setBinary(__DIR__.$ds."bin".$ds."ffmpegLinux");
        }
    }

    /**
     * set the binary of ffmpeg
     * @param string|null $binaryFile
     * @throws \Exception
     * @author Dawood Ikhlaq <daudmalik06@gmail.com>
     */
    public function setBinary($binaryFile=null)
    {
        if(!$binaryFile || !file_exists($binaryFile))
        {
            throw new \Exception("Wrong binary file:\"$binaryFile\" provided");
        }
        $this->binary=trim($binaryFile);
        $this->abortIfBinaryHasProblems();
    }

    /**
     * @return string the final command which would be run on the console
     * @author Dawood Ikhlaq <daudmalik06@gmail.com>
     */
    public function getCommandToRun()
    {
        return $this->command;
    }

    /**
     * @return array all the options already set
     * @author Dawood Ikhlaq <daudmalik06@gmail.com>
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * verifies the working of ffmpeg and
     * abort if there is problem running it
     * @throws \Exception
     * @author Dawood Ikhlaq <daudmalik06@gmail.com>
     */
    private function abortIfBinaryHasProblems()
    {
        $process=new Process([$this->binary, '-version']);
        $process->start();
        $process->wait();
        $output=$process->getOutput();
        if(!strstr($output,'version'))
        {
            throw new \Exception("There is problem running ffmpeg verify if it's available...");
        }
        unset($process);
    }

    /**
     * @return the currently used binary file
     */
    public function getBinary()
    {
        return $this->binary;
    }

    /**
     * @return string the root path of library
     * @author Dawood Ikhlaq <daudmalik06@gmail.com>
     */
    public function rootPath()
    {
        return __DIR__;
    }
}
