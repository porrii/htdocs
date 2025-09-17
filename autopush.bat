@echo off
cd D:\programs\xampp\htdocs
git add .
git commit -m "Auto commit %date% %time%"
git push origin main
