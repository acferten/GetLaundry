<?php

/**
 * @property TemplateData[] $dataArray
 */
class Template
{
    public $text;
    public $buttons;
    private $templateName;
    private $dataArray;
    private $lang;

    public function __construct($templateName, $lang = null,  $dataArray = null)
    {
        $this->templateName = $templateName;
        $this->dataArray = $dataArray;
        $this->lang = $lang;
    }

    public function Load(): Template
    {
        // загружаю шаблон из файла
        $templateText = file_get_contents(__DIR__ . "/modules/test_templates/templates/{$this->templateName}.txt");

        if ($this->lang) {
            preg_match_all("/<:$this->lang>(.*?)<\/:$this->lang>/s", $templateText, $text);
            $templateText = $text[1][0];
        }

        $templateText = str_replace("\n", "", $templateText);
        $templateText = str_replace("<:n>", "\n", $templateText);

        // заполняю переменные шаблона
        foreach ($this->dataArray as $item) {
            $templateText = str_replace($item->pattern, $item->replacement, $templateText);
        }

        // заполняю кнопки шаблона
        preg_match_all("/<:buttons>(.*?)<\/:buttons>/", $templateText, $matches);
        $templateButtons = $matches[1];

        $k = 1;
        foreach ($templateButtons as $templateButton) {
            $button = new Button($templateButton);
            if ($button->GetType() == Button::TextType) $button->SetData($k);

            $this->buttons[] = $button;

            $k++;
        }

        $this->text = preg_replace("/<:buttons>(.*?)<\/:buttons>/", "", $templateText);

        return $this;
    }

    public function LoadButtons()
    {
        foreach ($this->buttons as $key => $button) {
            $this->buttons[$key] = $button->PrepareToSend();
        }
    }
}

class TemplateData
{
    public $pattern;
    public $replacement;

    public function __construct($pattern, $replacement)
    {
        $this->pattern = $pattern;
        $this->replacement = $replacement;
    }
}

class Button
{
    const TextType = "text";
    const ButtonType = "button";
    const LinkType = "link";

    protected $text;
    protected $type;
    protected $data;
    protected $mailingId;

    public function __construct($buttonData)
    {
        $buttonData = explode(";", $buttonData);

        $this->text = $buttonData[0];
        $this->type = $buttonData[1];
        $this->data = $buttonData[2];
    }

    public function GetText()
    {
        return $this->text;
    }

    public function GetType()
    {
        return $this->type;
    }

    public function SetData($newData)
    {
        $this->data = $newData;
    }

    public function GetData()
    {
        return $this->data;
    }

    public function SetMailingId($newMailingId)
    {
        $this->mailingId = $newMailingId;
    }

    public function PrepareToSend(): array
    {
        $replyMarkup = [
            'text' => $this->text,
        ];

        switch ($this->type) {
            case self::LinkType:
                $replyMarkup["url"] = $this->data;
                break;
            case self::TextType:
                $replyMarkup["callback_data"] = "/usermailing_answer $this->mailingId $this->data";
                break;
            case self::ButtonType:
                $replyMarkup["callback_data"] = "$this->data";
                break;
        }

        return [$replyMarkup];
    }
}