Set WshShell = CreateObject("WScript.Shell")
WshShell.CurrentDirectory = "E:\chotu AI\bin\tunnel"
WshShell.Run "cmd /c start-service.bat", 0, False
