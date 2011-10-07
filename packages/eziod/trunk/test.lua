-- ClearOS EZIO-300 LCD Panel Configuration
-- Copyright (C) 2010 ClearFoundation

write_text(string.format("ClearBOX     %03d", 300));
f = io.open("/proc/loadavg");
if f ~= nil then
	for line in f:lines() do
		write_text_xy(0, 1, line);
		break
	end
	io.close(f);
end
sleep(2);

clear();
write_text("Show block: ");
cursor_show_block();
sleep(1);

clear();
write_text("Show uscore: ");
cursor_show_uscore();
sleep(1);

clear();
write_text("Hide cursor.");
cursor_hide();
sleep(1);

clear();
write_text("Scroll panel.");
for i = 0, 4, 1 do
	scroll_right();
	sleep(1);
end

for i = 0, 4, 1 do
	scroll_left();
	sleep(1);
end

choices = {};
choices[1] = "No";
choices[2] = "Yes";

choice = read_choice("Tea?", choices);
if choice == -1 then
	echo("no choice selected");
else
	echo("choice: " .. choices[choice]);
end

ip = "192.168.1.1";
ip = read_ipaddress("Address?", ip);
if ip == nil then
	echo("ip: escape");
else
	echo("ip: " .. ip);
end

clear();
write_text_xy(0, 0, "Press any key:");
write_text_xy(0, 1, "> ");
cursor_move(2, 1);
cursor_show_block();
button = read_button(5000000);
if button == EZIO_BUTTON_ESC then
	write_text_xy(2, 1, "Esc");
	echo("button: Esc");
elseif button == EZIO_BUTTON_ENTER then
	write_text_xy(2, 1, "Enter");
	echo("button: Enter");
elseif button == EZIO_BUTTON_UP then
	write_text_xy(2, 1, "Up");
	echo("button: Up");
elseif button == EZIO_BUTTON_DOWN then
	write_text_xy(2, 1, "Down");
	echo("button: Down");
elseif button == EZIO_BUTTON_NONE then
	write_text_xy(2, 1, "None");
	echo("button: None");
else
	write_text_xy(2, 1, string.format("%d", button));
	echo("button: " .. button);
end
cursor_hide();

