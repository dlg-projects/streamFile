#StreamFile

Stream File is a package that displays the partial content of a file

## Installation

You may use Composer to install StreamFile to your project:

```shell
composer require dlg-projects/streamfile
```

## Use

```php
use DlgProjects\StreamFile;

$stream = new StreamFile('myFolder/myFile.mp4');
$stream->startStream();
```

## Functions available

1. public functions:

- **getBufferSize** : get the size of the buffer used
- **getFile** : get the file that will be opened for stream
- **getFileName** : get the name that will be displayed in the HTML header
- **getFileSize** : get file size
- **getRangeEnd** : get the end of the range requested by HTML header
- **getRangeStart** : get the start of the range requested by HTML header
- **getTypeMime** : get mime type of file
- **setBufferSize** : define the size of the buffer (default: 524288 byte)
- **setFileName** : define the name of the file that will be sent in the header (default: the same as the file)
- **setFileSize** : define the file size. It's automatically set by the constructor. But it may be necessary to set it manually if the constructor cannot.
- **setTypeMime** : define the type mime. It's automatically set by the constructor but you can change it
- **startStream** : starts streaming the file

#License

This library is released under the [MIT license](LICENSE).