import 'dart:convert';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:http/http.dart' as http;
import 'package:http/io_client.dart';
import 'package:speech_to_text/speech_to_text.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const NajotLinkVoiceApp());
}

class NajotLinkVoiceApp extends StatelessWidget {
  const NajotLinkVoiceApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'MY NajotLink Mobil',
      theme: ThemeData.dark(useMaterial3: true),
      home: const VoiceSosScreen(),
    );
  }
}

class VoiceSosScreen extends StatefulWidget {
  const VoiceSosScreen({super.key});

  @override
  State<VoiceSosScreen> createState() => _VoiceSosScreenState();
}

class _VoiceSosScreenState extends State<VoiceSosScreen> {
  static const String _fallbackAddress =
      "Termiz shahri, I. Karimov ko'chasi 64-uy (Toshkent tibbiyot akademiyasi Termiz filiali)";
  static const double _fallbackLat = 37.224205;
  static const double _fallbackLng = 67.278317;

  final SpeechToText _speech = SpeechToText();
  final TextEditingController _apiCtrl = TextEditingController(
    text: 'https://87.237.235.107:4143/dashboard/06',
  );

  late final http.Client _client;
  bool _listening = false;
  bool _sending = false;
  String _status = "SOS tugmasini bosing va muammoni ayting";
  String _spoken = '';
  String _address = '-';
  double? _lat;
  double? _lng;
  String _result = '';

  @override
  void initState() {
    super.initState();
    _client = _buildHttpClient();
  }

  @override
  void dispose() {
    _apiCtrl.dispose();
    _client.close();
    super.dispose();
  }

  http.Client _buildHttpClient() {
    final io = HttpClient();
    io.badCertificateCallback = (cert, host, port) {
      return host == '87.237.235.107';
    };
    return IOClient(io);
  }

  Future<void> _onSosPressed() async {
    if (_sending) return;
    if (!_listening) {
      await _startListening();
    } else {
      await _stopAndSend();
    }
  }

  Future<void> _startListening() async {
    final ready = await _speech.initialize(
      onError: (e) {
        if (!mounted) return;
        setState(() {
          _listening = false;
          _status = 'Nutq xatoligi: ${e.errorMsg}';
        });
      },
      onStatus: (s) {
        if (!mounted) return;
        if (s == 'done') {
          setState(() => _listening = false);
        }
      },
    );

    if (!ready) {
      setState(() => _status = "Mikrofon ruxsati yo'q yoki speech xizmati topilmadi");
      return;
    }

    String locale = 'uz_UZ';
    try {
      final locales = await _speech.locales();
      final uz = locales.where((l) => l.localeId.toLowerCase().startsWith('uz')).toList();
      if (uz.isNotEmpty) {
        locale = uz.first.localeId;
      } else if (locales.isNotEmpty) {
        locale = locales.first.localeId;
      }
    } catch (_) {}

    setState(() {
      _listening = true;
      _spoken = '';
      _result = '';
      _status = 'Tinglanmoqda... gapiring';
    });

    await _speech.listen(
      localeId: locale,
      partialResults: true,
      listenFor: const Duration(seconds: 25),
      pauseFor: const Duration(seconds: 4),
      onResult: (res) {
        if (!mounted) return;
        setState(() {
          _spoken = res.recognizedWords.trim();
          _status = 'Ovoz matnga olinmoqda...';
        });
      },
    );
  }

  Future<void> _stopAndSend() async {
    await _speech.stop();
    setState(() => _listening = false);

    if (_spoken.trim().isEmpty) {
      setState(() => _status = "Matn aniqlanmadi. Iltimos, qayta urinib ko'ring.");
      return;
    }

    setState(() {
      _sending = true;
      _status = 'GPS olinmoqda...';
    });

    double lat = _fallbackLat;
    double lng = _fallbackLng;
    String locationText = _fallbackAddress;

    try {
      final pos = await _getLocation();
      lat = pos.latitude;
      lng = pos.longitude;
      locationText = "GPS nuqta: ${lat.toStringAsFixed(6)}, ${lng.toStringAsFixed(6)}";
    } catch (_) {}

    _lat = lat;
    _lng = lng;
    _address = locationText;

    try {
      setState(() => _status = 'SOS serverga yuborilmoqda...');
      final data = await _sendToServer(
        baseUrl: _apiCtrl.text.trim(),
        voiceText: _spoken,
        latitude: lat,
        longitude: lng,
        locationText: locationText,
      );

      setState(() {
        _status = "Yuborildi. Yo'naltirish: ${data['alert']?['service'] ?? '-'}";
        _result = jsonEncode(data);
      });
    } catch (e) {
      setState(() => _status = 'Xatolik: $e');
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  Future<Position> _getLocation() async {
    final enabled = await Geolocator.isLocationServiceEnabled();
    if (!enabled) throw Exception("GPS yoqilmagan");

    var perm = await Geolocator.checkPermission();
    if (perm == LocationPermission.denied) {
      perm = await Geolocator.requestPermission();
    }
    if (perm == LocationPermission.denied || perm == LocationPermission.deniedForever) {
      throw Exception("Lokatsiya ruxsati berilmagan");
    }

    return Geolocator.getCurrentPosition(desiredAccuracy: LocationAccuracy.high);
  }

  String _normalizedBaseUrl(String input) {
    final trimmed = input.trim();
    if (trimmed.endsWith('/')) return trimmed.substring(0, trimmed.length - 1);
    return trimmed;
  }

  Future<Map<String, dynamic>> _sendToServer({
    required String baseUrl,
    required String voiceText,
    required double latitude,
    required double longitude,
    required String locationText,
  }) async {
    final cleanBase = _normalizedBaseUrl(baseUrl);
    if (cleanBase.isEmpty) {
      throw Exception("API URL bo'sh");
    }

    final uri = Uri.parse('$cleanBase/sos-create.php');
    final response = await _client.post(
      uri,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'user_id': 'USR-001',
        'device_id': 'DEV-001',
        'voice_text': voiceText,
        'latitude': latitude,
        'longitude': longitude,
        'location_text': locationText,
      }),
    );

    final parsed = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode < 200 || response.statusCode >= 300 || parsed['ok'] != true) {
      throw Exception(parsed['message']?.toString() ?? "Server xatoligi");
    }
    return parsed;
  }

  @override
  Widget build(BuildContext context) {
    final buttonText = _sending
        ? 'Yuborilmoqda...'
        : (_listening ? "Yuborish uchun qayta bosing" : 'SOS');

    return Scaffold(
      appBar: AppBar(title: const Text('MY NajotLink Mobil')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: ListView(
          children: [
            TextField(
              controller: _apiCtrl,
              decoration: const InputDecoration(
                labelText: 'Server manzili (API)',
                hintText: 'https://87.237.235.107:4143/dashboard/06',
              ),
            ),
            const SizedBox(height: 12),
            Container(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.white24),
              ),
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Holat: $_status'),
                  const SizedBox(height: 8),
                  Text("Matn: ${_spoken.isEmpty ? '-' : _spoken}"),
                  const SizedBox(height: 8),
                  Text(
                    "GPS: ${_lat == null ? '-' : '${_lat!.toStringAsFixed(6)}, ${_lng!.toStringAsFixed(6)}'}",
                  ),
                  const SizedBox(height: 8),
                  Text("Manzil: $_address"),
                ],
              ),
            ),
            const SizedBox(height: 18),
            Center(
              child: GestureDetector(
                onTap: _onSosPressed,
                child: Container(
                  width: 220,
                  height: 220,
                  decoration: const BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: RadialGradient(
                      colors: [Color(0xFFFF9AB1), Color(0xFFFF4D6D), Color(0xFF7A0820)],
                    ),
                    boxShadow: [
                      BoxShadow(color: Color(0x66FF4D6D), blurRadius: 40, spreadRadius: 8),
                    ],
                  ),
                  child: Center(
                    child: Text(
                      buttonText,
                      textAlign: TextAlign.center,
                      style: const TextStyle(fontSize: 34, fontWeight: FontWeight.w800),
                    ),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 16),
            if (_result.isNotEmpty)
              Container(
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: Colors.white24),
                ),
                padding: const EdgeInsets.all(12),
                child: Text('Server javobi: $_result'),
              ),
          ],
        ),
      ),
    );
  }
}
